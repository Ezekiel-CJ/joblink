<?php
// =====================================================
// AI RECOMMENDATION ENGINE
// Supports OpenAI, Hugging Face, or local matching
// PHP 7.4+ Compatible - NO CACHING VERSION
// =====================================================

require_once __DIR__ . '/config.php';

function getAIConfig() {
    $db = getDB();
    $config = [
        'use_ai_api' => '0',
        'openai_api_key' => '',
        'ai_provider' => 'local'
    ];
    
    if ($db) {
        $result = $db->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('use_ai_api', 'openai_api_key', 'ai_provider')");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $config[$row['config_key']] = $row['config_value'];
            }
        }
    }
    return $config;
}

function isAIEnabled() {
    $config = getAIConfig();
    return isset($config['use_ai_api']) && $config['use_ai_api'] == '1' && !empty($config['openai_api_key']);
}

function getOpenAIRecommendation($prompt, $max_tokens = 500) {
    $config = getAIConfig();
    $api_key = isset($config['openai_api_key']) ? $config['openai_api_key'] : '';
    
    if (empty($api_key)) {
        return null;
    }
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a recruitment and job matching expert. Provide concise, actionable recommendations.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => $max_tokens,
        'temperature' => 0.7
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
    }
    
    error_log("OpenAI API Error: " . $response);
    return null;
}

// =====================================================
// AI-ASSISTED BEST APPLICANT RECOMMENDER
// =====================================================

function getBestApplicantsForJob($job_id, $limit = 5) {
    $db = getDB();
    if (!$db) return array();
    
    // Clear any old cache for this job to ensure fresh data
    $db->query("DELETE FROM ai_recommendations WHERE job_id = " . intval($job_id) . " AND recommendation_type = 'best_applicant'");
    
    // Get job details
    $job_result = $db->query("SELECT title, description, requirements, required_skills, education_level, years_experience 
                              FROM jobs WHERE id = " . intval($job_id));
    $job = $job_result ? $job_result->fetch_assoc() : null;
    
    if (!$job) {
        return array();
    }
    
    // Get all applicants with their profiles
    $applicants_result = $db->query("SELECT 
                                    a.id as application_id, 
                                    a.cover_letter, 
                                    a.resume_path, 
                                    a.status,
                                    u.id as user_id, 
                                    u.full_name, 
                                    u.email,
                                    IFNULL(jp.bio, '') as bio, 
                                    IFNULL(jp.education, '') as education, 
                                    IFNULL(jp.experience, '') as experience, 
                                    IFNULL(jp.skills, '') as skills
                                    FROM applications a
                                    INNER JOIN users u ON a.jobseeker_id = u.id
                                    LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
                                    WHERE a.job_id = " . intval($job_id) . " AND a.status != 'rejected'
                                    ORDER BY a.created_at DESC");
    
    if (!$applicants_result || $applicants_result->num_rows == 0) {
        return array();
    }
    
    $applicants_list = $applicants_result->fetch_all(MYSQLI_ASSOC);
    
    // Parse job skills
    $job_skills = array();
    if (!empty($job['required_skills'])) {
        $job_skills_raw = explode(',', $job['required_skills']);
        foreach ($job_skills_raw as $skill) {
            $skill_trimmed = trim($skill);
            if (!empty($skill_trimmed)) {
                $job_skills[] = $skill_trimmed;
            }
        }
    }
    
    // Calculate match scores
    $scored_applicants = array();
    foreach ($applicants_list as $applicant) {
        $score = 0;
        $reasons = array();
        
        // Skill matching (60% weight)
        $applicant_skills = array();
        if (!empty($applicant['skills'])) {
            $applicant_skills_raw = explode(',', $applicant['skills']);
            foreach ($applicant_skills_raw as $skill) {
                $skill_trimmed = trim($skill);
                if (!empty($skill_trimmed)) {
                    $applicant_skills[] = $skill_trimmed;
                }
            }
        }
        
        $skill_matches = 0;
        foreach ($job_skills as $job_skill) {
            foreach ($applicant_skills as $app_skill) {
                if (strtolower(trim($job_skill)) == strtolower(trim($app_skill))) {
                    $skill_matches++;
                    $reasons[] = "✓ Matched skill: " . $job_skill;
                    break;
                }
            }
        }
        
        $skill_score = count($job_skills) > 0 ? ($skill_matches / count($job_skills)) * 60 : 0;
        $score += $skill_score;
        
        // Experience matching (20% weight)
        if (!empty($applicant['experience'])) {
            $exp_years = extractYearsFromText($applicant['experience']);
            $required_years = isset($job['years_experience']) ? intval($job['years_experience']) : 0;
            if ($required_years > 0) {
                $exp_score = min(20, ($exp_years / $required_years) * 20);
                $score += $exp_score;
                $reasons[] = "✓ Experience: " . $exp_years . " years (required: " . $required_years . ")";
            } else {
                $score += 10;
                $reasons[] = "✓ Experience provided";
            }
        }
        
        // Education matching (10% weight)
        if (!empty($applicant['education']) && !empty($job['education_level'])) {
            if (educationLevelMatch($applicant['education'], $job['education_level'])) {
                $score += 10;
                $reasons[] = "✓ Education meets requirement: " . $job['education_level'];
            }
        } elseif (!empty($applicant['education'])) {
            $score += 5;
            $reasons[] = "✓ Education provided";
        }
        
        // Cover letter quality (10% weight)
        if (!empty($applicant['cover_letter'])) {
            $cover_length = strlen($applicant['cover_letter']);
            if ($cover_length > 500) {
                $score += 10;
                $reasons[] = "✓ Detailed cover letter provided";
            } elseif ($cover_length > 200) {
                $score += 7;
                $reasons[] = "✓ Cover letter provided";
            } else {
                $score += 3;
            }
        }
        
        $scored_applicants[] = array(
            'application_id' => $applicant['application_id'],
            'user_id' => $applicant['user_id'],
            'full_name' => !empty($applicant['full_name']) ? $applicant['full_name'] : 'Job Seeker',
            'email' => !empty($applicant['email']) ? $applicant['email'] : 'Not provided',
            'match_score' => round($score, 2),
            'skill_matches' => $skill_matches,
            'total_skills' => count($job_skills),
            'reasons' => $reasons,
            'skills' => $applicant['skills'],
            'education' => $applicant['education'],
            'experience' => $applicant['experience'],
            'bio' => $applicant['bio'],
            'cover_letter' => $applicant['cover_letter'],
            'resume_path' => $applicant['resume_path']
        );
    }
    
    // Sort by match score
    usort($scored_applicants, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
    
    return array_slice($scored_applicants, 0, $limit);
}

// =====================================================
// JOB RECOMMENDATIONS FOR JOB SEEKERS
// =====================================================

function getAIJobRecommendationsForSeeker($seeker_id, $limit = 10) {
    $db = getDB();
    if (!$db) return array();
    
    // Get seeker profile
    $seeker_result = $db->query("SELECT skills, education, experience FROM jobseeker_profiles WHERE user_id = " . intval($seeker_id));
    $seeker = $seeker_result ? $seeker_result->fetch_assoc() : null;
    
    $seeker_skills = array();
    if ($seeker && !empty($seeker['skills'])) {
        $skills_raw = explode(',', $seeker['skills']);
        foreach ($skills_raw as $skill) {
            $skill_trimmed = trim($skill);
            if (!empty($skill_trimmed)) {
                $seeker_skills[] = $skill_trimmed;
            }
        }
    }
    
    // Get all active jobs
    $jobs_result = $db->query("SELECT j.*, c.name as category_name, ep.business_name 
                               FROM jobs j
                               LEFT JOIN job_categories c ON j.category_id = c.id
                               LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
                               WHERE j.status = 'approved'
                               ORDER BY j.created_at DESC");
    
    if (!$jobs_result) {
        return array();
    }
    
    $jobs = $jobs_result->fetch_all(MYSQLI_ASSOC);
    $recommendations = array();
    
    foreach ($jobs as $job) {
        $job_skills = array();
        if (!empty($job['required_skills'])) {
            $skills_raw = explode(',', $job['required_skills']);
            foreach ($skills_raw as $skill) {
                $skill_trimmed = trim($skill);
                if (!empty($skill_trimmed)) {
                    $job_skills[] = $skill_trimmed;
                }
            }
        }
        
        $match_score = 0;
        $matched_skills = array();
        
        foreach ($seeker_skills as $s_skill) {
            foreach ($job_skills as $j_skill) {
                if (strtolower($s_skill) == strtolower($j_skill)) {
                    $match_score += 20;
                    $matched_skills[] = $j_skill;
                    break;
                }
            }
        }
        
        $match_score = min(100, $match_score);
        
        if ($match_score > 0) {
            $recommendations[] = array(
                'job_id' => $job['id'],
                'title' => $job['title'],
                'business_name' => isset($job['business_name']) ? $job['business_name'] : '',
                'location' => isset($job['location']) ? $job['location'] : '',
                'salary_min' => isset($job['salary_min']) ? $job['salary_min'] : 0,
                'salary_max' => isset($job['salary_max']) ? $job['salary_max'] : 0,
                'match_score' => $match_score,
                'matched_skills' => $matched_skills,
                'total_skills' => count($job_skills)
            );
        }
    }
    
    usort($recommendations, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
    
    return array_slice($recommendations, 0, $limit);
}

// =====================================================
// AI-ASSISTED INSIGHTS FOR LGU ADMIN
// =====================================================

function getAIWorkforceInsights($force_refresh = false) {
    $db = getDB();
    if (!$db) {
        return getDefaultInsights();
    }
    
    // Gather workforce data
    $total_jobs_result = $db->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'approved'");
    $total_jobs = $total_jobs_result ? intval($total_jobs_result->fetch_assoc()['count']) : 0;
    
    $total_apps_result = $db->query("SELECT COUNT(*) as count FROM applications");
    $total_applications = $total_apps_result ? intval($total_apps_result->fetch_assoc()['count']) : 0;
    
    $total_hires_result = $db->query("SELECT COUNT(*) as count FROM applications WHERE status = 'accepted'");
    $total_hires = $total_hires_result ? intval($total_hires_result->fetch_assoc()['count']) : 0;
    
    // Get skill demand
    $skills_data = $db->query("SELECT required_skills FROM jobs WHERE status = 'approved' AND required_skills IS NOT NULL");
    $skill_demand = array();
    if ($skills_data) {
        while ($row = $skills_data->fetch_assoc()) {
            if (!empty($row['required_skills'])) {
                $skills = explode(',', $row['required_skills']);
                foreach ($skills as $skill) {
                    $skill = trim($skill);
                    if (!empty($skill)) {
                        $skill_demand[$skill] = isset($skill_demand[$skill]) ? $skill_demand[$skill] + 1 : 1;
                    }
                }
            }
        }
    }
    arsort($skill_demand);
    $top_skills = array_slice($skill_demand, 0, 5, true);
    
    // Get top categories
    $categories_result = $db->query("SELECT c.name, COUNT(j.id) as count 
                                     FROM job_categories c 
                                     LEFT JOIN jobs j ON c.id = j.category_id AND j.status = 'approved'
                                     GROUP BY c.id 
                                     ORDER BY count DESC LIMIT 5");
    $category_data = array();
    if ($categories_result) {
        while ($cat = $categories_result->fetch_assoc()) {
            $category_data[] = $cat;
        }
    }
    
    return generateLocalInsights($top_skills, $category_data, $total_applications, $total_hires);
}

function getDefaultInsights() {
    return array(
        'insights' => array(
            'The local job market is showing steady growth.',
            'Skills-based matching is improving placement rates.',
            'Regular job postings lead to better hiring outcomes.'
        ),
        'recommendations' => array(
            'Encourage more employers to post jobs regularly.',
            'Promote skill development programs for job seekers.',
            'Consider hosting quarterly job fairs.'
        ),
        'summary' => 'Based on platform data, focus on skill development and employer engagement.'
    );
}

function generateLocalInsights($top_skills, $category_data, $total_applications, $total_hires) {
    $insights = array();
    $recommendations = array();
    
    // Skill gap insight
    $top_skill_names = array_keys($top_skills);
    if (!empty($top_skill_names)) {
        $skill_list = implode(", ", array_slice($top_skill_names, 0, 3));
        $insights[] = "The top in-demand skills in your community are: " . $skill_list;
        $recommendations[] = "Partner with TESDA or local training centers to offer courses in " . $top_skill_names[0];
    } else {
        $insights[] = "Add skill requirements to jobs for better matching insights.";
        $recommendations[] = "Encourage employers to list required skills when posting jobs.";
    }
    
    // Hiring rate insight
    if ($total_applications > 0) {
        $hire_rate = round(($total_hires / $total_applications) * 100, 1);
        $insights[] = "Current hiring rate is " . $hire_rate . "% with " . number_format($total_hires) . " successful placements.";
        if ($hire_rate < 20) {
            $recommendations[] = "Consider hosting job fairs or improving job matching algorithms to increase placement rates.";
        }
    } else {
        $insights[] = "Start collecting application data for better hiring insights.";
    }
    
    // Category insight
    if (!empty($category_data) && isset($category_data[0]['name'])) {
        $insights[] = ucfirst($category_data[0]['name']) . " sector has the most job opportunities with " . $category_data[0]['count'] . " active postings.";
        $recommendations[] = "Focus workforce development programs on " . $category_data[0]['name'] . "-related skills.";
    }
    
    if (empty($insights)) {
        $insights[] = "Continue growing your platform to generate valuable workforce insights.";
        $recommendations[] = "Promote the platform to more employers and job seekers.";
    }
    
    return array(
        'insights' => $insights,
        'recommendations' => $recommendations,
        'summary' => "Based on platform data, focus on upskilling for in-demand skills and supporting growing sectors."
    );
}

// =====================================================
// TOP RATED JOBS FOR LGU ADMIN
// =====================================================

function getTopRatedJobs($limit = 10) {
    $db = getDB();
    if (!$db) return array();
    
    $query = "SELECT j.*, 
              ep.business_name,
              c.name as category_name,
              COALESCE(AVG(jr.rating), 0) as avg_rating,
              COUNT(DISTINCT jr.id) as review_count,
              COUNT(DISTINCT a.id) as applicant_count,
              SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as hired_count
              FROM jobs j
              LEFT JOIN employer_profiles ep ON j.employer_id = ep.user_id
              LEFT JOIN job_categories c ON j.category_id = c.id
              LEFT JOIN job_ratings jr ON j.id = jr.job_id
              LEFT JOIN applications a ON j.id = a.job_id
              WHERE j.status = 'approved'
              GROUP BY j.id
              ORDER BY avg_rating DESC, applicant_count DESC
              LIMIT " . intval($limit);
    
    $result = $db->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : array();
}

function rateJob($job_id, $rating, $reviewer_id = null, $comment = null) {
    $db = getDB();
    if (!$db) return false;
    
    $rating = max(1, min(5, $rating));
    
    $stmt = $db->prepare("INSERT INTO job_ratings (job_id, rating, reviewed_by, review_comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $job_id, $rating, $reviewer_id, $comment);
    
    if ($stmt->execute()) {
        logAudit($reviewer_id, 'rate_job', 'job', $job_id, array('rating' => $rating));
        return true;
    }
    return false;
}

// =====================================================
// EMPLOYER ANALYTICS
// =====================================================

function trackEmployerMetric($employer_id, $job_id, $metric_type) {
    $db = getDB();
    if (!$db) return;
    
    $stmt = $db->prepare("INSERT INTO employer_analytics (employer_id, job_id, metric_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $employer_id, $job_id, $metric_type);
    $stmt->execute();
}

function getEmployerAnalytics($employer_id, $period = '30_days') {
    $db = getDB();
    if (!$db) {
        return array(
            'totals' => array('total_views' => 0, 'total_applications' => 0, 'total_shortlists' => 0, 'total_hires' => 0, 'jobs_with_applications' => 0),
            'trends' => array(),
            'conversion_rate' => 0,
            'hire_rate' => 0
        );
    }
    
    $interval = "INTERVAL 30 DAY";
    if ($period == '7_days') $interval = "INTERVAL 7 DAY";
    if ($period == '90_days') $interval = "INTERVAL 90 DAY";
    
    $query = "SELECT 
                SUM(CASE WHEN metric_type = 'job_view' THEN metric_value ELSE 0 END) as total_views,
                SUM(CASE WHEN metric_type = 'application' THEN metric_value ELSE 0 END) as total_applications,
                SUM(CASE WHEN metric_type = 'shortlist' THEN metric_value ELSE 0 END) as total_shortlists,
                SUM(CASE WHEN metric_type = 'hire' THEN metric_value ELSE 0 END) as total_hires,
                COUNT(DISTINCT CASE WHEN metric_type = 'application' THEN job_id END) as jobs_with_applications
              FROM employer_analytics 
              WHERE employer_id = " . intval($employer_id) . " 
              AND created_at >= NOW() - " . $interval;
    
    $result = $db->query($query);
    $totals = $result ? $result->fetch_assoc() : array('total_views' => 0, 'total_applications' => 0, 'total_shortlists' => 0, 'total_hires' => 0, 'jobs_with_applications' => 0);
    
    // Get daily trends
    $trends_result = $db->query("SELECT 
                                  DATE(created_at) as date,
                                  SUM(CASE WHEN metric_type = 'job_view' THEN metric_value ELSE 0 END) as views,
                                  SUM(CASE WHEN metric_type = 'application' THEN metric_value ELSE 0 END) as applications
                                FROM employer_analytics 
                                WHERE employer_id = " . intval($employer_id) . " 
                                AND created_at >= NOW() - " . $interval . "
                                GROUP BY DATE(created_at)
                                ORDER BY date DESC
                                LIMIT 30");
    
    $trends = $trends_result ? $trends_result->fetch_all(MYSQLI_ASSOC) : array();
    
    $total_views = isset($totals['total_views']) ? intval($totals['total_views']) : 0;
    $total_apps = isset($totals['total_applications']) ? intval($totals['total_applications']) : 0;
    $total_hires = isset($totals['total_hires']) ? intval($totals['total_hires']) : 0;
    
    return array(
        'totals' => $totals,
        'trends' => $trends,
        'conversion_rate' => $total_views > 0 ? round(($total_apps / $total_views) * 100, 1) : 0,
        'hire_rate' => $total_apps > 0 ? round(($total_hires / $total_apps) * 100, 1) : 0
    );
}

// Helper functions
function extractYearsFromText($text) {
    preg_match_all('/(\d+)\s*(?:years?|yrs?)/i', $text, $matches);
    if (!empty($matches[1])) {
        return max($matches[1]);
    }
    return 0;
}

function educationLevelMatch($education_text, $required_level) {
    $education_text = strtolower($education_text);
    $levels = array(
        'phd' => array('phd', 'doctorate', 'doctoral'),
        'master' => array('master', 'ms', 'ma', 'mba', 'graduate'),
        'bachelor' => array('bachelor', 'bs', 'ba', 'undergraduate', 'college'),
        'associate' => array('associate', 'vocational', 'tech-voc'),
        'high_school' => array('high school', 'hs', 'secondary')
    );
    
    if (isset($levels[$required_level])) {
        foreach ($levels[$required_level] as $keyword) {
            if (strpos($education_text, $keyword) !== false) {
                return true;
            }
        }
    }
    return false;
}
?>