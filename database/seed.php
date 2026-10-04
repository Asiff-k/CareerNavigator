<?php
/*
 * CareerNavigator database seeder.
 *
 * Run once from the terminal:   C:\xampp\php\php.exe database\seed.php
 *
 * 1. Creates the two extra tables (advisor_notes, interview_attempts) and adds the email verification columns.
 * 2. Inserts skills, careers, required skills, courses and interview questions.
 * 3. Creates demo admin, advisor and student accounts.
 *
 * It is safe to run again: existing rows (matched by name/title/email) are skipped.
 */

if (PHP_SAPI !== 'cli') {
    die('Run this script from the command line.');
}

require_once __DIR__ . '/../config/db.php';

// ===== 1. Extra tables =====
$pdo->exec(file_get_contents(__DIR__ . '/schema_additions.sql'));
echo "Extra tables ready.\n";
require __DIR__ . '/migrate_email_verification.php';

// ===== 2. Skills =====
$skills = [
    ['HTML & CSS', 'Web Development'],
    ['JavaScript', 'Programming'],
    ['React', 'Web Development'],
    ['Responsive Design', 'Web Development'],
    ['PHP', 'Programming'],
    ['Python', 'Programming'],
    ['Java', 'Programming'],
    ['Kotlin', 'Mobile Development'],
    ['Flutter/Dart', 'Mobile Development'],
    ['SQL', 'Database'],
    ['Database Design', 'Database'],
    ['REST APIs', 'Web Development'],
    ['Object-Oriented Programming', 'Computer Science'],
    ['Data Structures & Algorithms', 'Computer Science'],
    ['Git & Version Control', 'Tools'],
    ['Linux', 'Systems'],
    ['Networking', 'Systems'],
    ['Cloud Computing', 'Cloud & DevOps'],
    ['Docker', 'Cloud & DevOps'],
    ['CI/CD', 'Cloud & DevOps'],
    ['Cybersecurity Fundamentals', 'Security'],
    ['Statistics', 'Data'],
    ['Excel', 'Data'],
    ['Data Visualization', 'Data'],
    ['Machine Learning', 'Artificial Intelligence'],
    ['Deep Learning', 'Artificial Intelligence'],
    ['UI/UX Design', 'Design'],
    ['Figma', 'Design'],
    ['Software Testing', 'Quality Assurance'],
    ['Business Analysis', 'Business'],
    ['Project Management', 'Business'],
    ['Communication', 'Soft Skills'],
    ['Problem Solving', 'Soft Skills'],
    ['Teamwork', 'Soft Skills'],
];

$insertSkill = $pdo->prepare("INSERT IGNORE INTO skills (name, category) VALUES (?, ?)");
foreach ($skills as $s) {
    $insertSkill->execute($s);
}

// Map skill name => id
$skillId = [];
foreach ($pdo->query("SELECT id, name FROM skills") as $row) {
    $skillId[$row['name']] = (int) $row['id'];
}
echo count($skillId) . " skills ready.\n";

// ===== 3. Careers and required skills =====
// Each skill: [skill name, required level (1=beginner, 2=intermediate, 3=advanced), importance (1-5)]
$careers = [
    [
        'title' => 'Frontend Developer',
        'category' => 'Software Development',
        'salary' => '$55,000 - $95,000 per year',
        'description' => 'Builds the visual, interactive parts of websites and web applications that users see in the browser. Works with web development technologies, user interfaces, responsive layouts and modern JavaScript frameworks.',
        'skills' => [
            ['HTML & CSS', 3, 5], ['JavaScript', 3, 5], ['React', 2, 4], ['Responsive Design', 2, 4],
            ['Git & Version Control', 2, 3], ['REST APIs', 1, 2], ['UI/UX Design', 1, 2], ['Problem Solving', 2, 3],
        ],
    ],
    [
        'title' => 'Backend Developer',
        'category' => 'Software Development',
        'salary' => '$60,000 - $105,000 per year',
        'description' => 'Designs and builds the server-side logic, databases and APIs that power web development and mobile applications. Focuses on web servers, data storage, security and performance.',
        'skills' => [
            ['PHP', 2, 4], ['SQL', 3, 5], ['Database Design', 2, 5], ['REST APIs', 3, 5],
            ['Object-Oriented Programming', 2, 4], ['Git & Version Control', 2, 3], ['Linux', 1, 2], ['Problem Solving', 2, 3],
        ],
    ],
    [
        'title' => 'Full Stack Developer',
        'category' => 'Software Development',
        'salary' => '$65,000 - $110,000 per year',
        'description' => 'Works on both the frontend and backend of web applications, from user interfaces to servers and databases. A versatile web development role suited to building complete products.',
        'skills' => [
            ['HTML & CSS', 2, 4], ['JavaScript', 3, 5], ['PHP', 2, 4], ['SQL', 2, 4], ['REST APIs', 2, 4],
            ['Database Design', 2, 3], ['Git & Version Control', 2, 3], ['Responsive Design', 1, 2], ['Problem Solving', 2, 3],
        ],
    ],
    [
        'title' => 'Data Analyst',
        'category' => 'Data Science',
        'salary' => '$50,000 - $85,000 per year',
        'description' => 'Collects, cleans and interprets data to help organisations make better decisions. Creates reports, dashboards and data visualizations using statistics, spreadsheets and SQL. Ideal for people interested in data and business.',
        'skills' => [
            ['SQL', 3, 5], ['Excel', 3, 4], ['Statistics', 2, 5], ['Data Visualization', 2, 4],
            ['Python', 1, 3], ['Communication', 2, 3], ['Problem Solving', 2, 3],
        ],
    ],
    [
        'title' => 'Data Scientist',
        'category' => 'Data Science',
        'salary' => '$75,000 - $130,000 per year',
        'description' => 'Uses statistics, programming and machine learning to discover patterns in data, build predictive models and solve business problems with artificial intelligence (AI) techniques.',
        'skills' => [
            ['Python', 3, 5], ['Statistics', 3, 5], ['Machine Learning', 2, 5], ['SQL', 2, 4],
            ['Data Visualization', 2, 3], ['Data Structures & Algorithms', 1, 2], ['Communication', 2, 2],
        ],
    ],
    [
        'title' => 'Machine Learning Engineer',
        'category' => 'Artificial Intelligence',
        'salary' => '$85,000 - $145,000 per year',
        'description' => 'Designs, trains and deploys machine learning and deep learning models into production systems. Combines artificial intelligence (AI) research, data and software engineering.',
        'skills' => [
            ['Python', 3, 5], ['Machine Learning', 3, 5], ['Deep Learning', 2, 4], ['Data Structures & Algorithms', 2, 4],
            ['Statistics', 2, 4], ['Docker', 1, 2], ['Cloud Computing', 1, 2], ['Git & Version Control', 2, 2],
        ],
    ],
    [
        'title' => 'DevOps Engineer',
        'category' => 'Cloud & Infrastructure',
        'salary' => '$70,000 - $125,000 per year',
        'description' => 'Automates software delivery and manages cloud infrastructure, servers and deployment pipelines so that applications are reliable and scalable. Suited to people interested in cloud, automation and systems.',
        'skills' => [
            ['Linux', 3, 5], ['Docker', 2, 5], ['CI/CD', 2, 5], ['Cloud Computing', 2, 4],
            ['Git & Version Control', 3, 4], ['Networking', 1, 3], ['Python', 1, 2],
        ],
    ],
    [
        'title' => 'Cybersecurity Analyst',
        'category' => 'Security',
        'salary' => '$65,000 - $115,000 per year',
        'description' => 'Protects computer systems and networks from cyber attacks by monitoring threats, finding vulnerabilities and responding to security incidents. Ideal for people interested in security, hacking and networking.',
        'skills' => [
            ['Cybersecurity Fundamentals', 3, 5], ['Networking', 3, 5], ['Linux', 2, 4],
            ['Python', 1, 2], ['Problem Solving', 2, 3], ['Communication', 1, 2],
        ],
    ],
    [
        'title' => 'UI/UX Designer',
        'category' => 'Design',
        'salary' => '$50,000 - $95,000 per year',
        'description' => 'Researches user needs and designs intuitive, attractive interfaces for websites and mobile apps through wireframes, prototypes and usability testing. Suited to creative people interested in design.',
        'skills' => [
            ['UI/UX Design', 3, 5], ['Figma', 3, 5], ['Responsive Design', 2, 3],
            ['HTML & CSS', 1, 2], ['Communication', 2, 4], ['Problem Solving', 1, 2],
        ],
    ],
    [
        'title' => 'Mobile App Developer',
        'category' => 'Software Development',
        'salary' => '$60,000 - $110,000 per year',
        'description' => 'Builds mobile applications for Android and iOS smartphones and tablets, focusing on performance, offline use and a great mobile user experience.',
        'skills' => [
            ['Java', 2, 4], ['Kotlin', 2, 3], ['Flutter/Dart', 2, 3], ['Object-Oriented Programming', 2, 4],
            ['REST APIs', 2, 3], ['Git & Version Control', 2, 3], ['UI/UX Design', 1, 2],
        ],
    ],
    [
        'title' => 'QA Engineer',
        'category' => 'Quality Assurance',
        'salary' => '$50,000 - $90,000 per year',
        'description' => 'Ensures software quality by planning and running manual and automated testing, reporting bugs and improving the development process. Suited to detail-oriented people interested in quality and testing.',
        'skills' => [
            ['Software Testing', 3, 5], ['JavaScript', 1, 3], ['SQL', 1, 2], ['Git & Version Control', 1, 2],
            ['Problem Solving', 2, 4], ['Communication', 2, 3],
        ],
    ],
    [
        'title' => 'Business Analyst',
        'category' => 'Business & Management',
        'salary' => '$55,000 - $95,000 per year',
        'description' => 'Bridges business and technology by analysing requirements, processes and data, and communicating solutions between stakeholders and development teams. Suited to people interested in business and management.',
        'skills' => [
            ['Business Analysis', 3, 5], ['Communication', 3, 5], ['Excel', 2, 4], ['SQL', 1, 3],
            ['Project Management', 2, 3], ['Data Visualization', 1, 2], ['Teamwork', 2, 3],
        ],
    ],
];

$findCareer = $pdo->prepare("SELECT id FROM careers WHERE title = ?");
$insertCareer = $pdo->prepare("INSERT INTO careers (title, description, category, average_salary) VALUES (?, ?, ?, ?)");
$insertCareerSkill = $pdo->prepare(
    "INSERT IGNORE INTO career_skills (career_id, skill_id, required_level, importance) VALUES (?, ?, ?, ?)"
);

$careerId = [];
foreach ($careers as $c) {
    $findCareer->execute([$c['title']]);
    $id = $findCareer->fetchColumn();
    if (!$id) {
        $insertCareer->execute([$c['title'], $c['description'], $c['category'], $c['salary']]);
        $id = $pdo->lastInsertId();
    }
    $careerId[$c['title']] = (int) $id;

    foreach ($c['skills'] as [$skillName, $level, $importance]) {
        $insertCareerSkill->execute([$id, $skillId[$skillName], $level, $importance]);
    }
}
echo count($careerId) . " careers ready.\n";

// ===== 4. Courses and certifications =====
// [title, provider, description, url, skill name, [career titles]]
$courses = [
    ['Responsive Web Design Certification', 'freeCodeCamp', 'Free certification covering HTML, CSS, Flexbox, Grid and accessibility through hands-on projects.', 'https://www.freecodecamp.org/learn/2022/responsive-web-design/', 'HTML & CSS', ['Frontend Developer', 'Full Stack Developer', 'UI/UX Designer']],
    ['Learn Responsive Design', 'web.dev (Google)', 'Free course on building layouts that adapt to every screen size.', 'https://web.dev/learn/design', 'Responsive Design', ['Frontend Developer', 'UI/UX Designer']],
    ['JavaScript Algorithms and Data Structures', 'freeCodeCamp', 'Free certification teaching JavaScript fundamentals, ES6, debugging and algorithm scripting.', 'https://www.freecodecamp.org/learn/javascript-algorithms-and-data-structures-v8/', 'JavaScript', ['Frontend Developer', 'Full Stack Developer', 'QA Engineer']],
    ['React Official Tutorial', 'react.dev', 'The official guide to components, state, props and hooks in React.', 'https://react.dev/learn', 'React', ['Frontend Developer']],
    ['Meta Front-End Developer Professional Certificate', 'Coursera (Meta)', 'Professional certificate covering HTML, CSS, JavaScript, React and UX principles.', 'https://www.coursera.org/professional-certificates/meta-front-end-developer', 'React', ['Frontend Developer', 'Full Stack Developer']],
    ['PHP Tutorial', 'W3Schools', 'Beginner-friendly PHP tutorial with examples on forms, sessions, MySQL and OOP.', 'https://www.w3schools.com/php/', 'PHP', ['Backend Developer', 'Full Stack Developer']],
    ['PHP: The Right Way', 'phptherightway.com', 'Community guide to modern PHP best practices, security and coding standards.', 'https://phptherightway.com/', 'PHP', ['Backend Developer']],
    ['Python for Everybody Specialization', 'Coursera (University of Michigan)', 'Popular introduction to programming and data handling with Python.', 'https://www.coursera.org/specializations/python', 'Python', ['Data Analyst', 'Data Scientist', 'Machine Learning Engineer']],
    ['Java Programming and Software Engineering Fundamentals', 'Coursera (Duke University)', 'Learn Java programming and core software engineering principles.', 'https://www.coursera.org/specializations/java-programming', 'Java', ['Mobile App Developer']],
    ['Android Basics with Compose', 'Google (Android Developers)', 'Free official course on building Android apps with Kotlin and Jetpack Compose.', 'https://developer.android.com/courses/android-basics-compose/course', 'Kotlin', ['Mobile App Developer']],
    ['Flutter: Get Started', 'Google (Flutter)', 'Official guide to building cross-platform mobile apps with Flutter and Dart.', 'https://docs.flutter.dev/get-started', 'Flutter/Dart', ['Mobile App Developer']],
    ['SQL Tutorial', 'W3Schools', 'Learn SQL queries, joins, grouping and database manipulation with live examples.', 'https://www.w3schools.com/sql/', 'SQL', ['Backend Developer', 'Data Analyst', 'Business Analyst']],
    ['Relational Database Certification', 'freeCodeCamp', 'Free certification on PostgreSQL, database design, Bash and Git.', 'https://www.freecodecamp.org/learn/relational-database/', 'Database Design', ['Backend Developer', 'Full Stack Developer']],
    ['Back End Development and APIs', 'freeCodeCamp', 'Free certification on building REST APIs and microservices with Node.js and Express.', 'https://www.freecodecamp.org/learn/back-end-development-and-apis/', 'REST APIs', ['Backend Developer', 'Full Stack Developer']],
    ['Object Oriented Programming in Java', 'Coursera (UC San Diego)', 'Learn classes, inheritance, polymorphism and interfaces by building projects.', 'https://www.coursera.org/learn/object-oriented-java', 'Object-Oriented Programming', ['Backend Developer', 'Mobile App Developer']],
    ['Algorithms, Part I', 'Coursera (Princeton University)', 'Essential data structures and algorithms: sorting, searching, trees and graphs.', 'https://www.coursera.org/learn/algorithms-part1', 'Data Structures & Algorithms', ['Data Scientist', 'Machine Learning Engineer']],
    ['Learn Git Branching', 'learngitbranching.js.org', 'Interactive visual tutorial for Git commands and branching.', 'https://learngitbranching.js.org/', 'Git & Version Control', ['Frontend Developer', 'Backend Developer', 'DevOps Engineer']],
    ['Introduction to Linux (LFS101)', 'The Linux Foundation', 'Free course on the Linux command line, file systems and system administration.', 'https://training.linuxfoundation.org/training/introduction-to-linux/', 'Linux', ['DevOps Engineer', 'Cybersecurity Analyst']],
    ['Networking Basics', 'Cisco Networking Academy', 'Free course covering network types, IP addressing, protocols and network security basics.', 'https://www.netacad.com/courses/networking-basics', 'Networking', ['Cybersecurity Analyst', 'DevOps Engineer']],
    ['AWS Certified Cloud Practitioner', 'Amazon Web Services', 'Entry-level certification on AWS cloud concepts, services, security and pricing.', 'https://aws.amazon.com/certification/certified-cloud-practitioner/', 'Cloud Computing', ['DevOps Engineer', 'Machine Learning Engineer']],
    ['Docker: Get Started', 'Docker', 'Official guide to containers, images and Docker Compose.', 'https://docs.docker.com/get-started/', 'Docker', ['DevOps Engineer', 'Machine Learning Engineer']],
    ['GitHub Actions Documentation', 'GitHub', 'Learn to build CI/CD pipelines that test and deploy code automatically.', 'https://docs.github.com/en/actions', 'CI/CD', ['DevOps Engineer']],
    ['CompTIA Security+', 'CompTIA', 'Globally recognised entry-level cybersecurity certification.', 'https://www.comptia.org/certifications/security', 'Cybersecurity Fundamentals', ['Cybersecurity Analyst']],
    ['Google Cybersecurity Professional Certificate', 'Coursera (Google)', 'Hands-on certificate covering security frameworks, Linux, SQL, Python and SIEM tools.', 'https://www.coursera.org/professional-certificates/google-cybersecurity', 'Cybersecurity Fundamentals', ['Cybersecurity Analyst']],
    ['Statistics and Probability', 'Khan Academy', 'Free lessons on descriptive statistics, probability, distributions and hypothesis testing.', 'https://www.khanacademy.org/math/statistics-probability', 'Statistics', ['Data Analyst', 'Data Scientist', 'Machine Learning Engineer']],
    ['Excel Skills for Business Specialization', 'Coursera (Macquarie University)', 'Develop Excel skills from basics to advanced formulas, charts and data analysis.', 'https://www.coursera.org/specializations/excel', 'Excel', ['Data Analyst', 'Business Analyst']],
    ['Google Data Analytics Professional Certificate', 'Coursera (Google)', 'Learn data cleaning, analysis and visualization with spreadsheets, SQL, Tableau and R.', 'https://www.coursera.org/professional-certificates/google-data-analytics', 'Data Visualization', ['Data Analyst', 'Business Analyst']],
    ['Machine Learning Specialization', 'Coursera (DeepLearning.AI & Stanford)', 'Andrew Ng\'s beginner-friendly introduction to supervised and unsupervised learning.', 'https://www.coursera.org/specializations/machine-learning-introduction', 'Machine Learning', ['Data Scientist', 'Machine Learning Engineer']],
    ['Deep Learning Specialization', 'Coursera (DeepLearning.AI)', 'Neural networks, CNNs, RNNs and best practices for deep learning projects.', 'https://www.coursera.org/specializations/deep-learning', 'Deep Learning', ['Machine Learning Engineer']],
    ['Google UX Design Professional Certificate', 'Coursera (Google)', 'Learn the UX design process: research, wireframing, prototyping and usability testing.', 'https://www.coursera.org/professional-certificates/google-ux-design', 'UI/UX Design', ['UI/UX Designer']],
    ['Figma Learn Design', 'Figma', 'Official Figma lessons on interface design, components and prototyping.', 'https://www.figma.com/resources/learn-design/', 'Figma', ['UI/UX Designer']],
    ['ISTQB Certified Tester Foundation Level', 'ISTQB', 'International certification covering software testing fundamentals and techniques.', 'https://www.istqb.org/certifications/certified-tester-foundation-level', 'Software Testing', ['QA Engineer']],
    ['Software Testing and Automation Specialization', 'Coursera (University of Minnesota)', 'Learn black-box, white-box and automated testing techniques.', 'https://www.coursera.org/specializations/software-testing-automation', 'Software Testing', ['QA Engineer']],
    ['IIBA Entry Certificate in Business Analysis (ECBA)', 'IIBA', 'Entry-level certification recognising business analysis knowledge.', 'https://www.iiba.org/business-analysis-certifications/ecba/', 'Business Analysis', ['Business Analyst']],
    ['Google Project Management Professional Certificate', 'Coursera (Google)', 'Learn project planning, Agile, Scrum and stakeholder management.', 'https://www.coursera.org/professional-certificates/google-project-management', 'Project Management', ['Business Analyst']],
    ['Improving Communication Skills', 'Coursera (University of Pennsylvania)', 'Practical techniques for persuasive and effective workplace communication.', 'https://www.coursera.org/learn/wharton-communication-skills', 'Communication', ['Business Analyst', 'UI/UX Designer']],
    ['Effective Problem-Solving and Decision-Making', 'Coursera (UC Irvine)', 'Structured methods for analysing problems and making better decisions.', 'https://www.coursera.org/learn/problem-solving', 'Problem Solving', ['QA Engineer']],
    ['Teamwork Skills: Communicating Effectively in Groups', 'Coursera (University of Colorado Boulder)', 'Improve collaboration, group communication and conflict management.', 'https://www.coursera.org/learn/teamwork-skills-effective-communication', 'Teamwork', ['Business Analyst']],
];

$findCourse = $pdo->prepare("SELECT id FROM courses WHERE title = ?");
$insertCourse = $pdo->prepare("INSERT INTO courses (title, provider, description, course_url, skill_id) VALUES (?, ?, ?, ?, ?)");
$insertCareerCourse = $pdo->prepare("INSERT IGNORE INTO career_courses (career_id, course_id) VALUES (?, ?)");

foreach ($courses as [$title, $provider, $description, $url, $skillName, $careerTitles]) {
    $findCourse->execute([$title]);
    $id = $findCourse->fetchColumn();
    if (!$id) {
        $insertCourse->execute([$title, $provider, $description, $url, $skillId[$skillName]]);
        $id = $pdo->lastInsertId();
    }
    foreach ($careerTitles as $careerTitle) {
        $insertCareerCourse->execute([$careerId[$careerTitle], $id]);
    }
}
echo count($courses) . " courses ready.\n";

// ===== 5. Interview questions =====
// [career title or null for general, question, model answer, difficulty]
$questions = [
    [null, 'Tell me about yourself.', 'Give a short, structured summary: your current studies or role, key skills and achievements, a relevant project, and why you are interested in this position and company.', 'easy'],
    [null, 'Describe a challenging project and how you handled it.', 'Use the STAR method: describe the Situation, the Task you were responsible for, the Actions you took (planning, teamwork, problem solving) and the Result, including what you learned.', 'medium'],
    [null, 'Why should we hire you?', 'Connect your skills, projects and attitude to the job requirements. Mention specific strengths, evidence of learning quickly, teamwork and how you will add value to the team.', 'easy'],
    [null, 'Where do you see yourself in five years?', 'Show ambition aligned with the company: growing technical skills, taking more responsibility, mentoring others and contributing to larger projects within the organisation.', 'easy'],

    ['Frontend Developer', 'What is the difference between let, const and var in JavaScript?', 'var is function-scoped and hoisted; let and const are block-scoped. let can be reassigned, const cannot be reassigned after declaration, although objects declared with const can still be mutated.', 'easy'],
    ['Frontend Developer', 'Explain the CSS box model.', 'Every element is a box made of content, padding, border and margin. box-sizing: border-box includes padding and border in the declared width and height, which makes layouts easier.', 'easy'],
    ['Frontend Developer', 'How do you make a website responsive?', 'Use a mobile-first approach with fluid layouts, Flexbox and Grid, relative units, responsive images and media queries, plus the viewport meta tag. Test on different screen sizes.', 'medium'],
    ['Frontend Developer', 'What are React hooks and why are they useful?', 'Hooks such as useState and useEffect let function components manage state and side effects. They make components simpler, reusable through custom hooks and avoid class components.', 'medium'],
    ['Frontend Developer', 'How would you improve the performance of a slow web page?', 'Measure with Lighthouse, then reduce and compress assets, lazy load images, minify and bundle code, use caching and a CDN, avoid layout thrashing and reduce blocking JavaScript.', 'hard'],

    ['Backend Developer', 'What is a REST API?', 'A REST API exposes resources through URLs and uses HTTP methods such as GET, POST, PUT and DELETE. It is stateless, uses status codes and usually exchanges data in JSON format.', 'easy'],
    ['Backend Developer', 'How do you prevent SQL injection?', 'Never concatenate user input into queries. Use prepared statements with bound parameters (for example PDO), validate input, apply least privilege database accounts and escape output.', 'medium'],
    ['Backend Developer', 'What is database normalization?', 'Normalization organises tables to reduce redundancy and improve integrity. First normal form removes repeating groups, second removes partial dependencies, third removes transitive dependencies.', 'medium'],
    ['Backend Developer', 'How should passwords be stored?', 'Passwords must never be stored in plain text. Store a slow, salted hash using algorithms such as bcrypt or Argon2, for example PHP password_hash, and verify with password_verify.', 'easy'],
    ['Backend Developer', 'How would you scale a backend that is receiving too much traffic?', 'Profile bottlenecks, add caching, optimise database queries and indexes, use load balancing and horizontal scaling, queues for background jobs and read replicas for the database.', 'hard'],

    ['Full Stack Developer', 'Explain what happens when you type a URL into the browser.', 'The browser resolves the domain with DNS, opens a TCP and TLS connection, sends an HTTP request, the server processes it and returns a response, then the browser parses HTML, CSS and JavaScript and renders the page.', 'medium'],
    ['Full Stack Developer', 'What is the difference between sessions and cookies?', 'Cookies are stored in the browser and sent with every request. Sessions store data on the server and the browser only keeps a session ID cookie, which is more secure for sensitive data.', 'easy'],
    ['Full Stack Developer', 'How do the frontend and backend communicate?', 'Through HTTP requests, usually to a REST or GraphQL API. The frontend sends requests with fetch or forms, and the backend returns JSON or HTML with suitable status codes.', 'easy'],
    ['Full Stack Developer', 'How would you design authentication for a web application?', 'Hash passwords, use secure sessions or tokens, regenerate session IDs on login, use HTTPS, protect forms with CSRF tokens, enforce role-based authorization and rate-limit login attempts.', 'hard'],

    ['Data Analyst', 'What is the difference between WHERE and HAVING in SQL?', 'WHERE filters rows before grouping, while HAVING filters groups after GROUP BY using aggregate functions such as COUNT or SUM.', 'easy'],
    ['Data Analyst', 'How do you handle missing data?', 'First understand why data is missing, then remove rows, impute values using mean, median or mode, use a separate category or model-based imputation. Document the decision and its impact.', 'medium'],
    ['Data Analyst', 'What is the difference between mean and median?', 'The mean is the average of all values, while the median is the middle value. The median is more robust when the data contains outliers or is skewed.', 'easy'],
    ['Data Analyst', 'How do you choose the right chart for your data?', 'Choose based on the message: bar charts compare categories, line charts show trends over time, scatter plots show relationships, histograms show distributions. Keep charts simple and labelled.', 'medium'],

    ['Data Scientist', 'Explain overfitting and how to prevent it.', 'Overfitting happens when a model learns noise in the training data and performs poorly on new data. Prevent it with cross-validation, more data, regularization, simpler models and early stopping.', 'medium'],
    ['Data Scientist', 'What is the difference between supervised and unsupervised learning?', 'Supervised learning uses labelled data to predict outputs, such as classification and regression. Unsupervised learning finds patterns in unlabelled data, such as clustering.', 'easy'],
    ['Data Scientist', 'What is a p-value?', 'A p-value is the probability of seeing results at least as extreme as observed, assuming the null hypothesis is true. A small p-value suggests evidence against the null hypothesis.', 'medium'],
    ['Data Scientist', 'How do you evaluate a classification model?', 'Use a confusion matrix, accuracy, precision, recall, F1 score and ROC AUC. Choose metrics based on the business problem, especially when classes are imbalanced.', 'hard'],

    ['Machine Learning Engineer', 'What is gradient descent?', 'Gradient descent is an optimization algorithm that updates model parameters in the opposite direction of the gradient of the loss function, using a learning rate, to minimise error.', 'medium'],
    ['Machine Learning Engineer', 'What is the bias-variance tradeoff?', 'Bias is error from overly simple assumptions, variance is error from sensitivity to training data. Increasing model complexity lowers bias but raises variance; the goal is a balance.', 'medium'],
    ['Machine Learning Engineer', 'How do you deploy a machine learning model to production?', 'Package the model and dependencies, often in a Docker container, expose it through an API, monitor predictions and data drift, version models and automate retraining with pipelines.', 'hard'],
    ['Machine Learning Engineer', 'What is a neural network?', 'A neural network is a model made of layers of connected neurons. Each neuron applies weights, a bias and an activation function; the network learns weights using backpropagation.', 'easy'],

    ['DevOps Engineer', 'What is CI/CD?', 'Continuous Integration automatically builds and tests code whenever changes are merged. Continuous Delivery or Deployment automatically releases tested code to staging or production.', 'easy'],
    ['DevOps Engineer', 'What is the difference between a container and a virtual machine?', 'A virtual machine includes a full guest operating system on a hypervisor. A container shares the host kernel and packages only the application and dependencies, so it is lighter and faster to start.', 'medium'],
    ['DevOps Engineer', 'What is Infrastructure as Code?', 'Infrastructure as Code manages servers and cloud resources using version-controlled configuration files, with tools such as Terraform or Ansible, making environments repeatable and automated.', 'medium'],
    ['DevOps Engineer', 'How would you handle a production outage?', 'Communicate with stakeholders, check monitoring and logs, roll back or mitigate quickly, find the root cause, fix it, then write a blameless post-mortem and add alerts to prevent recurrence.', 'hard'],

    ['Cybersecurity Analyst', 'What is the CIA triad?', 'Confidentiality, Integrity and Availability. Confidentiality protects data from unauthorised access, integrity ensures data is accurate and unaltered, availability keeps systems accessible.', 'easy'],
    ['Cybersecurity Analyst', 'What is the difference between symmetric and asymmetric encryption?', 'Symmetric encryption uses one shared key for encryption and decryption, such as AES. Asymmetric encryption uses a public and private key pair, such as RSA, often for key exchange.', 'medium'],
    ['Cybersecurity Analyst', 'How would you respond to a phishing incident?', 'Identify affected users, isolate compromised accounts or devices, reset credentials, block malicious domains, analyse logs, remove the email, inform users and document lessons learned.', 'hard'],
    ['Cybersecurity Analyst', 'What is a firewall?', 'A firewall monitors and filters network traffic based on security rules, allowing or blocking connections to protect a network from unauthorised access.', 'easy'],

    ['UI/UX Designer', 'What is the difference between UI and UX?', 'UX is the overall experience and usability of a product, based on research and user needs. UI is the visual interface: layout, colours, typography and interactive elements.', 'easy'],
    ['UI/UX Designer', 'Describe your design process.', 'Research users, define problems and personas, ideate, create wireframes and prototypes, run usability testing, iterate based on feedback and hand off designs to developers.', 'medium'],
    ['UI/UX Designer', 'How do you design for accessibility?', 'Use sufficient colour contrast, readable fonts, keyboard navigation, alt text, clear labels and focus states, and follow WCAG guidelines. Test with assistive technology.', 'medium'],
    ['UI/UX Designer', 'How do you handle negative feedback on your design?', 'Listen without being defensive, understand the reasoning, connect feedback to user goals and data, test alternatives and iterate. Feedback improves the product.', 'easy'],

    ['Mobile App Developer', 'Explain the Android activity lifecycle.', 'An activity moves through onCreate, onStart, onResume, onPause, onStop and onDestroy. Developers save state and release resources in the correct lifecycle methods.', 'medium'],
    ['Mobile App Developer', 'What is the difference between native and cross-platform development?', 'Native apps use platform languages such as Kotlin or Swift for best performance. Cross-platform frameworks such as Flutter share one codebase for Android and iOS, saving time.', 'easy'],
    ['Mobile App Developer', 'How do you make a mobile app work offline?', 'Cache data locally using a database such as SQLite or Room, queue changes and sync with the server when the connection returns, and handle conflicts gracefully.', 'hard'],
    ['Mobile App Developer', 'What are the four principles of object-oriented programming?', 'Encapsulation, abstraction, inheritance and polymorphism. They help organise code into reusable, maintainable classes and objects.', 'easy'],

    ['QA Engineer', 'What is the difference between verification and validation?', 'Verification checks that the product is built correctly according to specifications (are we building it right). Validation checks that it meets user needs (are we building the right product).', 'medium'],
    ['QA Engineer', 'What makes a good bug report?', 'A clear title, steps to reproduce, expected and actual results, environment details, severity, priority and screenshots or logs.', 'easy'],
    ['QA Engineer', 'What are unit, integration and system testing?', 'Unit testing checks individual functions, integration testing checks modules working together, and system testing checks the complete application against requirements.', 'easy'],
    ['QA Engineer', 'When should a test be automated?', 'Automate tests that are repetitive, stable, run often such as regression tests, or are data-driven. Keep exploratory and usability testing manual.', 'medium'],

    ['Business Analyst', 'How do you gather requirements?', 'Identify stakeholders, then use interviews, workshops, surveys, observation and document analysis. Record requirements as user stories or use cases and validate them with stakeholders.', 'easy'],
    ['Business Analyst', 'What is a SWOT analysis?', 'SWOT analyses Strengths, Weaknesses, Opportunities and Threats to support strategic decisions about a business, product or project.', 'easy'],
    ['Business Analyst', 'How do you handle conflicting stakeholder requirements?', 'Understand each stakeholder goal, find common objectives, use data and prioritisation techniques such as MoSCoW, facilitate discussion and document agreed decisions.', 'hard'],
    ['Business Analyst', 'What is the difference between functional and non-functional requirements?', 'Functional requirements describe what the system should do. Non-functional requirements describe quality attributes such as performance, security, usability and reliability.', 'medium'],
];

$findQuestion = $pdo->prepare("SELECT id FROM interview_questions WHERE question = ?");
$insertQuestion = $pdo->prepare("INSERT INTO interview_questions (career_id, question, answer, difficulty) VALUES (?, ?, ?, ?)");
foreach ($questions as [$careerTitle, $question, $answer, $difficulty]) {
    $findQuestion->execute([$question]);
    if (!$findQuestion->fetchColumn()) {
        $insertQuestion->execute([$careerTitle ? $careerId[$careerTitle] : null, $question, $answer, $difficulty]);
    }
}
echo count($questions) . " interview questions ready.\n";

// ===== 6. Demo accounts =====
$accounts = [
    ['System Admin', 'admin@careernavigator.com', 'admin123', 'admin'],
    ['Career Advisor', 'advisor@careernavigator.com', 'advisor123', 'advisor'],
    ['Demo Student', 'student@careernavigator.com', 'student123', 'student'],
];
$findUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
// Demo accounts are created already verified so they can sign in straight away.
$insertUser = $pdo->prepare("INSERT INTO users (name, email, password, role, email_verified_at) VALUES (?, ?, ?, ?, NOW())");
foreach ($accounts as [$name, $email, $password, $role]) {
    $findUser->execute([$email]);
    if (!$findUser->fetchColumn()) {
        $insertUser->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    }
}

// Give the demo student a profile, skills and projects so the platform can be demonstrated.
$findUser->execute(['student@careernavigator.com']);
$studentId = (int) $findUser->fetchColumn();

$pdo->prepare(
    "INSERT IGNORE INTO student_profiles (user_id, university, department, semester, cgpa, interests, bio)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
)->execute([
    $studentId, 'Sample University', 'Computer Science and Engineering', '7th Semester', 3.45,
    'Web Development, Data, Artificial Intelligence',
    'Final-year CSE student who enjoys building web applications.',
]);

$studentSkills = [
    'HTML & CSS' => 'advanced', 'JavaScript' => 'intermediate', 'PHP' => 'intermediate',
    'SQL' => 'intermediate', 'Git & Version Control' => 'beginner', 'Python' => 'beginner',
    'Problem Solving' => 'intermediate', 'Communication' => 'intermediate',
];
$insertStudentSkill = $pdo->prepare("INSERT IGNORE INTO student_skills (user_id, skill_id, proficiency) VALUES (?, ?, ?)");
foreach ($studentSkills as $skillName => $level) {
    $insertStudentSkill->execute([$studentId, $skillId[$skillName], $level]);
}

$projectCount = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ?");
$projectCount->execute([$studentId]);
if ((int) $projectCount->fetchColumn() === 0) {
    $insertProject = $pdo->prepare("INSERT INTO projects (user_id, title, description, technologies, project_url) VALUES (?, ?, ?, ?, ?)");
    $insertProject->execute([$studentId, 'Library Management System', 'Web application to manage books, members and borrowing records.', 'PHP, MySQL, HTML, CSS', null]);
    $insertProject->execute([$studentId, 'Weather Dashboard', 'Responsive dashboard that shows live weather using a public REST API.', 'JavaScript, REST API, CSS', null]);
}

echo "Demo accounts ready:\n";
echo "  admin@careernavigator.com   / admin123\n";
echo "  advisor@careernavigator.com / advisor123\n";
echo "  student@careernavigator.com / student123\n";
echo "Seeding complete.\n";
