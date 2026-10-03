<?php
require_once 'includes/init.php';
$pageTitle = "CareerNavigator - Find Your Career Path";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Bricolage+Grotesque:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --green: #14342B;
            --dark: #001F17;
            --lime: #BCF541;
            --background: #FAF9F7;
            --muted: #68736E;
            --border: #E5E3DC;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--dark);
        }

        .container {
            width: 90%;
            max-width: 1180px;
            margin: auto;
        }

        /* Navigation */

        nav {
            height: 82px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border);
            background: var(--background);
        }

        .nav-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 25px;
            font-weight: 800;
            color: var(--green);
            text-decoration: none;
        }

        .logo span {
            color: #82AD16;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-links a {
            color: #45534D;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: var(--green);
        }

        .nav-button {
            background: var(--green);
            color: white !important;
            padding: 12px 22px;
            border-radius: 7px;
        }

        /* Hero section */

        .hero {
            padding: 105px 0 100px;
            background:
                radial-gradient(circle at 85% 25%, #E8F4D4 0, transparent 30%),
                var(--background);
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            align-items: center;
            gap: 65px;
        }

        .eyebrow {
            display: inline-block;
            background: #EDF5DF;
            color: #496B16;
            padding: 9px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 25px;
        }

        h1 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: clamp(42px, 5vw, 66px);
            line-height: 1.08;
            letter-spacing: -2px;
            margin-bottom: 24px;
        }

        h1 span {
            color: #729C19;
        }

        .hero-description {
            max-width: 520px;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 32px;
        }

        .hero-buttons {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .button {
            display: inline-block;
            padding: 15px 24px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s;
        }

        .button-primary {
            background: var(--green);
            color: white;
        }

        .button-primary:hover {
            background: #245344;
            transform: translateY(-2px);
        }

        .button-secondary {
            border: 1px solid var(--border);
            color: var(--green);
            background: white;
        }

        .button-secondary:hover {
            border-color: var(--green);
        }

        /* Dashboard preview */

        .preview {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 20px 60px rgba(20, 52, 43, 0.08);
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .preview-header h3 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 19px;
        }

        .status {
            background: #EDF5DF;
            color: #527512;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .career-card {
            border: 1px solid var(--border);
            padding: 17px;
            border-radius: 10px;
            margin-bottom: 13px;
        }

        .career-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 13px;
        }

        .career-name {
            font-weight: 700;
            font-size: 14px;
        }

        .percentage {
            font-weight: 800;
            color: #648D13;
            font-size: 14px;
        }

        .progress {
            width: 100%;
            height: 7px;
            background: #EDF0E9;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: #A7D83C;
            border-radius: 20px;
        }

        .career-note {
            font-size: 11px;
            color: var(--muted);
            margin-top: 10px;
        }

        .preview-footer {
            margin-top: 20px;
            padding: 15px;
            background: #F3F7EB;
            border-radius: 9px;
            font-size: 12px;
            color: #496B16;
            line-height: 1.6;
        }

        /* Features */

        .features {
            padding: 95px 0;
            background: white;
        }

        .section-heading {
            text-align: center;
            max-width: 650px;
            margin: 0 auto 50px;
        }

        .section-heading h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 39px;
            margin-bottom: 15px;
        }

        .section-heading p {
            color: var(--muted);
            line-height: 1.7;
            font-size: 14px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .feature-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 28px;
            transition: 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(20, 52, 43, 0.06);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            background: #EDF5DF;
            border-radius: 10px;
            font-size: 22px;
            margin-bottom: 22px;
        }

        .feature-card h3 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 19px;
            margin-bottom: 12px;
        }

        .feature-card p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.8;
        }

        /* How it works */

        .how-it-works {
            padding: 95px 0;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .step {
            padding: 25px;
        }

        .step-number {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 35px;
            font-weight: 800;
            color: #8AB62C;
            margin-bottom: 15px;
        }

        .step h3 {
            font-size: 17px;
            margin-bottom: 10px;
        }

        .step p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        /* Call to action */

        .cta {
            padding: 65px 30px;
            background: var(--green);
            color: white;
            text-align: center;
            border-radius: 16px;
            margin-bottom: 90px;
        }

        .cta h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 36px;
            margin-bottom: 15px;
        }

        .cta p {
            color: #D0DDD6;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.7;
        }

        .cta .button {
            background: var(--lime);
            color: var(--dark);
        }

        /* Footer */

        footer {
            border-top: 1px solid var(--border);
            padding: 28px 0;
            background: white;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        footer p {
            color: var(--muted);
            font-size: 12px;
        }

        /* Responsive */

        @media (max-width: 850px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 45px;
            }

            .feature-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .nav-links {
                gap: 15px;
            }
        }

        @media (max-width: 600px) {
            nav {
                height: auto;
                padding: 18px 0;
            }

            .nav-content {
                align-items: flex-start;
                gap: 15px;
                flex-direction: column;
            }

            .nav-links {
                width: 100%;
                flex-wrap: wrap;
                gap: 18px;
            }

            .hero {
                padding: 65px 0;
            }

            .feature-grid,
            .steps {
                grid-template-columns: 1fr;
            }

            .section-heading h2,
            .cta h2 {
                font-size: 29px;
            }

            .preview {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav>
        <div class="container nav-content">

            <a href="index.php" class="logo">
                Career<span>Navigator.</span>
            </a>

            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a href="#about">About</a>
                <?php if (is_logged_in()): ?>
                    <a href="<?= e(home_for_role(current_user()['role'])) ?>" class="nav-button">My Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="nav-button">Sign In</a>
                <?php endif; ?>
            </div>

        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container hero-content">

            <div>
                <div class="eyebrow">
                    YOUR FUTURE, YOUR DIRECTION
                </div>

                <h1>
                    Discover the career that <span>fits you.</span>
                </h1>

                <p class="hero-description">
                    Your skills, interests, and academic background tell a
                    story. CareerNavigator helps you understand that story
                    and discover career paths that match your potential.
                </p>

                <div class="hero-buttons">
                    <a href="register.php" class="button button-primary">
                        Get Started &rarr;
                    </a>

                    <a href="#how-it-works" class="button button-secondary">
                        Explore How It Works
                    </a>
                </div>
            </div>

            <!-- Recommendation Preview -->
            <div class="preview">

                <div class="preview-header">
                    <h3>Career Compatibility</h3>
                    <span class="status">PROFILE ANALYSIS</span>
                </div>

                <div class="career-card">
                    <div class="career-top">
                        <span class="career-name">Frontend Developer</span>
                        <span class="percentage">92%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width:92%"></div>
                    </div>
                    <p class="career-note">Strong match with your technical skills</p>
                </div>

                <div class="career-card">
                    <div class="career-top">
                        <span class="career-name">Backend Developer</span>
                        <span class="percentage">84%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width:84%"></div>
                    </div>
                    <p class="career-note">Good alignment with your interests</p>
                </div>

                <div class="career-card">
                    <div class="career-top">
                        <span class="career-name">Data Analyst</span>
                        <span class="percentage">76%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width:76%"></div>
                    </div>
                    <p class="career-note">Potential match with room to grow</p>
                </div>

                <div class="preview-footer">
                    Your actual career recommendations will be generated
                    from your profile, skills, interests, and projects.
                </div>

            </div>

        </div>
    </section>

    <!-- Features -->
    <section class="features" id="features">
        <div class="container">

            <div class="section-heading">
                <h2>Everything you need to move forward</h2>
                <p>
                    Understand your strengths, identify skill gaps, and
                    create a practical learning path toward your career goals.
                </p>
            </div>

            <div class="feature-grid">

                <div class="feature-card">
                    <div class="feature-icon">◎</div>
                    <h3>Career Recommendations</h3>
                    <p>
                        Explore career paths based on your academic profile,
                        technical skills, projects, and interests.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">↗</div>
                    <h3>Skill Gap Analysis</h3>
                    <p>
                        Compare your existing skills with career requirements
                        and identify what you need to improve.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">▤</div>
                    <h3>Personalized Roadmaps</h3>
                    <p>
                        Follow structured learning steps to develop the skills
                        needed for your selected career.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">✧</div>
                    <h3>Learning Resources</h3>
                    <p>
                        Discover relevant courses and certifications that
                        support your professional development.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">▧</div>
                    <h3>Resume Analysis</h3>
                    <p>
                        Upload your resume and review how your experience
                        and skills align with career requirements.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">✎</div>
                    <h3>Interview Preparation</h3>
                    <p>
                        Practice career-related interview questions and
                        prepare for your next opportunity.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">

            <div class="section-heading">
                <h2>Your career journey starts here</h2>
                <p>
                    Three simple steps to understand your potential
                    and explore your opportunities.
                </p>
            </div>

            <div class="steps">

                <div class="step">
                    <div class="step-number">01</div>
                    <h3>Build Your Profile</h3>
                    <p>
                        Add your academic information, skills, interests,
                        certifications, and projects.
                    </p>
                </div>

                <div class="step">
                    <div class="step-number">02</div>
                    <h3>Discover Your Matches</h3>
                    <p>
                        Analyze your profile to discover relevant careers,
                        compatibility scores, and skill gaps.
                    </p>
                </div>

                <div class="step">
                    <div class="step-number">03</div>
                    <h3>Follow Your Roadmap</h3>
                    <p>
                        Explore learning resources and track your progress
                        toward your career goals.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- About / CTA -->
    <section class="container" id="about">
        <div class="cta">
            <h2>Your next chapter starts with a direction.</h2>
            <p>
                Take the first step toward understanding your career
                opportunities and building your future.
            </p>
            <a href="register.php" class="button">
                Create Your Profile &rarr;
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <a href="index.php" class="logo">
                Career<span>Navigator.</span>
            </a>

            <p>
                &copy; <?php echo date("Y"); ?> CareerNavigator.
                All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>
