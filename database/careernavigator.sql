-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: careernavigator
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `careernavigator`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `careernavigator` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `careernavigator`;

--
-- Table structure for table `advisor_notes`
--

DROP TABLE IF EXISTS `advisor_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `advisor_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `advisor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `advisor_id` (`advisor_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `advisor_notes_ibfk_1` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `advisor_notes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `advisor_notes`
--

LOCK TABLES `advisor_notes` WRITE;
/*!40000 ALTER TABLE `advisor_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `advisor_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `career_courses`
--

DROP TABLE IF EXISTS `career_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `career_courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `career_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_career_course` (`career_id`,`course_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `career_courses_ibfk_1` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `career_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `career_courses`
--

LOCK TABLES `career_courses` WRITE;
/*!40000 ALTER TABLE `career_courses` DISABLE KEYS */;
INSERT INTO `career_courses` VALUES (1,1,1),(4,1,2),(6,1,3),(9,1,4),(10,1,5),(32,1,17),(12,2,6),(14,2,7),(21,2,12),(24,2,13),(26,2,14),(28,2,15),(33,2,17),(2,3,1),(7,3,3),(11,3,5),(13,3,6),(25,3,13),(27,3,14),(15,4,8),(22,4,12),(46,4,25),(49,4,26),(51,4,27),(16,5,8),(30,5,16),(47,5,25),(53,5,28),(17,6,8),(31,6,16),(40,6,20),(42,6,21),(48,6,25),(54,6,28),(55,6,29),(34,7,17),(35,7,18),(38,7,19),(39,7,20),(41,7,21),(43,7,22),(36,8,18),(37,8,19),(44,8,23),(45,8,24),(3,9,1),(5,9,2),(56,9,30),(57,9,31),(63,9,36),(18,10,9),(19,10,10),(20,10,11),(29,10,15),(8,11,3),(58,11,32),(59,11,33),(64,11,37),(23,12,12),(50,12,26),(52,12,27),(60,12,34),(61,12,35),(62,12,36),(65,12,38);
/*!40000 ALTER TABLE `career_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `career_skills`
--

DROP TABLE IF EXISTS `career_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `career_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `career_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `required_level` tinyint(4) DEFAULT 1,
  `importance` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_career_skill` (`career_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  CONSTRAINT `career_skills_ibfk_1` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `career_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `career_skills`
--

LOCK TABLES `career_skills` WRITE;
/*!40000 ALTER TABLE `career_skills` DISABLE KEYS */;
INSERT INTO `career_skills` VALUES (1,1,1,3,5),(2,1,2,3,5),(3,1,3,2,4),(4,1,4,2,4),(5,1,15,2,3),(6,1,12,1,2),(7,1,27,1,2),(8,1,33,2,3),(9,2,5,2,4),(10,2,10,3,5),(11,2,11,2,5),(12,2,12,3,5),(13,2,13,2,4),(14,2,15,2,3),(15,2,16,1,2),(16,2,33,2,3),(17,3,1,2,4),(18,3,2,3,5),(19,3,5,2,4),(20,3,10,2,4),(21,3,12,2,4),(22,3,11,2,3),(23,3,15,2,3),(24,3,4,1,2),(25,3,33,2,3),(26,4,10,3,5),(27,4,23,3,4),(28,4,22,2,5),(29,4,24,2,4),(30,4,6,1,3),(31,4,32,2,3),(32,4,33,2,3),(33,5,6,3,5),(34,5,22,3,5),(35,5,25,2,5),(36,5,10,2,4),(37,5,24,2,3),(38,5,14,1,2),(39,5,32,2,2),(40,6,6,3,5),(41,6,25,3,5),(42,6,26,2,4),(43,6,14,2,4),(44,6,22,2,4),(45,6,19,1,2),(46,6,18,1,2),(47,6,15,2,2),(48,7,16,3,5),(49,7,19,2,5),(50,7,20,2,5),(51,7,18,2,4),(52,7,15,3,4),(53,7,17,1,3),(54,7,6,1,2),(55,8,21,3,5),(56,8,17,3,5),(57,8,16,2,4),(58,8,6,1,2),(59,8,33,2,3),(60,8,32,1,2),(61,9,27,3,5),(62,9,28,3,5),(63,9,4,2,3),(64,9,1,1,2),(65,9,32,2,4),(66,9,33,1,2),(67,10,7,2,4),(68,10,8,2,3),(69,10,9,2,3),(70,10,13,2,4),(71,10,12,2,3),(72,10,15,2,3),(73,10,27,1,2),(74,11,29,3,5),(75,11,2,1,3),(76,11,10,1,2),(77,11,15,1,2),(78,11,33,2,4),(79,11,32,2,3),(80,12,30,3,5),(81,12,32,3,5),(82,12,23,2,4),(83,12,10,1,3),(84,12,31,2,3),(85,12,24,1,2),(86,12,34,2,3);
/*!40000 ALTER TABLE `career_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `careers`
--

DROP TABLE IF EXISTS `careers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `careers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `average_salary` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `careers`
--

LOCK TABLES `careers` WRITE;
/*!40000 ALTER TABLE `careers` DISABLE KEYS */;
INSERT INTO `careers` VALUES (1,'Frontend Developer','Builds the visual, interactive parts of websites and web applications that users see in the browser. Works with web development technologies, user interfaces, responsive layouts and modern JavaScript frameworks.','Software Development','$55,000 - $95,000 per year'),(2,'Backend Developer','Designs and builds the server-side logic, databases and APIs that power web development and mobile applications. Focuses on web servers, data storage, security and performance.','Software Development','$60,000 - $105,000 per year'),(3,'Full Stack Developer','Works on both the frontend and backend of web applications, from user interfaces to servers and databases. A versatile web development role suited to building complete products.','Software Development','$65,000 - $110,000 per year'),(4,'Data Analyst','Collects, cleans and interprets data to help organisations make better decisions. Creates reports, dashboards and data visualizations using statistics, spreadsheets and SQL. Ideal for people interested in data and business.','Data Science','$50,000 - $85,000 per year'),(5,'Data Scientist','Uses statistics, programming and machine learning to discover patterns in data, build predictive models and solve business problems with artificial intelligence (AI) techniques.','Data Science','$75,000 - $130,000 per year'),(6,'Machine Learning Engineer','Designs, trains and deploys machine learning and deep learning models into production systems. Combines artificial intelligence (AI) research, data and software engineering.','Artificial Intelligence','$85,000 - $145,000 per year'),(7,'DevOps Engineer','Automates software delivery and manages cloud infrastructure, servers and deployment pipelines so that applications are reliable and scalable. Suited to people interested in cloud, automation and systems.','Cloud & Infrastructure','$70,000 - $125,000 per year'),(8,'Cybersecurity Analyst','Protects computer systems and networks from cyber attacks by monitoring threats, finding vulnerabilities and responding to security incidents. Ideal for people interested in security, hacking and networking.','Security','$65,000 - $115,000 per year'),(9,'UI/UX Designer','Researches user needs and designs intuitive, attractive interfaces for websites and mobile apps through wireframes, prototypes and usability testing. Suited to creative people interested in design.','Design','$50,000 - $95,000 per year'),(10,'Mobile App Developer','Builds mobile applications for Android and iOS smartphones and tablets, focusing on performance, offline use and a great mobile user experience.','Software Development','$60,000 - $110,000 per year'),(11,'QA Engineer','Ensures software quality by planning and running manual and automated testing, reporting bugs and improving the development process. Suited to detail-oriented people interested in quality and testing.','Quality Assurance','$50,000 - $90,000 per year'),(12,'Business Analyst','Bridges business and technology by analysing requirements, processes and data, and communicating solutions between stakeholders and development teams. Suited to people interested in business and management.','Business & Management','$55,000 - $95,000 per year');
/*!40000 ALTER TABLE `careers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `provider` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `course_url` varchar(255) DEFAULT NULL,
  `skill_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `skill_id` (`skill_id`),
  CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES (1,'Responsive Web Design Certification','freeCodeCamp','Free certification covering HTML, CSS, Flexbox, Grid and accessibility through hands-on projects.','https://www.freecodecamp.org/learn/2022/responsive-web-design/',1),(2,'Learn Responsive Design','web.dev (Google)','Free course on building layouts that adapt to every screen size.','https://web.dev/learn/design',4),(3,'JavaScript Algorithms and Data Structures','freeCodeCamp','Free certification teaching JavaScript fundamentals, ES6, debugging and algorithm scripting.','https://www.freecodecamp.org/learn/javascript-algorithms-and-data-structures-v8/',2),(4,'React Official Tutorial','react.dev','The official guide to components, state, props and hooks in React.','https://react.dev/learn',3),(5,'Meta Front-End Developer Professional Certificate','Coursera (Meta)','Professional certificate covering HTML, CSS, JavaScript, React and UX principles.','https://www.coursera.org/professional-certificates/meta-front-end-developer',3),(6,'PHP Tutorial','W3Schools','Beginner-friendly PHP tutorial with examples on forms, sessions, MySQL and OOP.','https://www.w3schools.com/php/',5),(7,'PHP: The Right Way','phptherightway.com','Community guide to modern PHP best practices, security and coding standards.','https://phptherightway.com/',5),(8,'Python for Everybody Specialization','Coursera (University of Michigan)','Popular introduction to programming and data handling with Python.','https://www.coursera.org/specializations/python',6),(9,'Java Programming and Software Engineering Fundamentals','Coursera (Duke University)','Learn Java programming and core software engineering principles.','https://www.coursera.org/specializations/java-programming',7),(10,'Android Basics with Compose','Google (Android Developers)','Free official course on building Android apps with Kotlin and Jetpack Compose.','https://developer.android.com/courses/android-basics-compose/course',8),(11,'Flutter: Get Started','Google (Flutter)','Official guide to building cross-platform mobile apps with Flutter and Dart.','https://docs.flutter.dev/get-started',9),(12,'SQL Tutorial','W3Schools','Learn SQL queries, joins, grouping and database manipulation with live examples.','https://www.w3schools.com/sql/',10),(13,'Relational Database Certification','freeCodeCamp','Free certification on PostgreSQL, database design, Bash and Git.','https://www.freecodecamp.org/learn/relational-database/',11),(14,'Back End Development and APIs','freeCodeCamp','Free certification on building REST APIs and microservices with Node.js and Express.','https://www.freecodecamp.org/learn/back-end-development-and-apis/',12),(15,'Object Oriented Programming in Java','Coursera (UC San Diego)','Learn classes, inheritance, polymorphism and interfaces by building projects.','https://www.coursera.org/learn/object-oriented-java',13),(16,'Algorithms, Part I','Coursera (Princeton University)','Essential data structures and algorithms: sorting, searching, trees and graphs.','https://www.coursera.org/learn/algorithms-part1',14),(17,'Learn Git Branching','learngitbranching.js.org','Interactive visual tutorial for Git commands and branching.','https://learngitbranching.js.org/',15),(18,'Introduction to Linux (LFS101)','The Linux Foundation','Free course on the Linux command line, file systems and system administration.','https://training.linuxfoundation.org/training/introduction-to-linux/',16),(19,'Networking Basics','Cisco Networking Academy','Free course covering network types, IP addressing, protocols and network security basics.','https://www.netacad.com/courses/networking-basics',17),(20,'AWS Certified Cloud Practitioner','Amazon Web Services','Entry-level certification on AWS cloud concepts, services, security and pricing.','https://aws.amazon.com/certification/certified-cloud-practitioner/',18),(21,'Docker: Get Started','Docker','Official guide to containers, images and Docker Compose.','https://docs.docker.com/get-started/',19),(22,'GitHub Actions Documentation','GitHub','Learn to build CI/CD pipelines that test and deploy code automatically.','https://docs.github.com/en/actions',20),(23,'CompTIA Security+','CompTIA','Globally recognised entry-level cybersecurity certification.','https://www.comptia.org/certifications/security',21),(24,'Google Cybersecurity Professional Certificate','Coursera (Google)','Hands-on certificate covering security frameworks, Linux, SQL, Python and SIEM tools.','https://www.coursera.org/professional-certificates/google-cybersecurity',21),(25,'Statistics and Probability','Khan Academy','Free lessons on descriptive statistics, probability, distributions and hypothesis testing.','https://www.khanacademy.org/math/statistics-probability',22),(26,'Excel Skills for Business Specialization','Coursera (Macquarie University)','Develop Excel skills from basics to advanced formulas, charts and data analysis.','https://www.coursera.org/specializations/excel',23),(27,'Google Data Analytics Professional Certificate','Coursera (Google)','Learn data cleaning, analysis and visualization with spreadsheets, SQL, Tableau and R.','https://www.coursera.org/professional-certificates/google-data-analytics',24),(28,'Machine Learning Specialization','Coursera (DeepLearning.AI & Stanford)','Andrew Ng\'s beginner-friendly introduction to supervised and unsupervised learning.','https://www.coursera.org/specializations/machine-learning-introduction',25),(29,'Deep Learning Specialization','Coursera (DeepLearning.AI)','Neural networks, CNNs, RNNs and best practices for deep learning projects.','https://www.coursera.org/specializations/deep-learning',26),(30,'Google UX Design Professional Certificate','Coursera (Google)','Learn the UX design process: research, wireframing, prototyping and usability testing.','https://www.coursera.org/professional-certificates/google-ux-design',27),(31,'Figma Learn Design','Figma','Official Figma lessons on interface design, components and prototyping.','https://www.figma.com/resources/learn-design/',28),(32,'ISTQB Certified Tester Foundation Level','ISTQB','International certification covering software testing fundamentals and techniques.','https://www.istqb.org/certifications/certified-tester-foundation-level',29),(33,'Software Testing and Automation Specialization','Coursera (University of Minnesota)','Learn black-box, white-box and automated testing techniques.','https://www.coursera.org/specializations/software-testing-automation',29),(34,'IIBA Entry Certificate in Business Analysis (ECBA)','IIBA','Entry-level certification recognising business analysis knowledge.','https://www.iiba.org/business-analysis-certifications/ecba/',30),(35,'Google Project Management Professional Certificate','Coursera (Google)','Learn project planning, Agile, Scrum and stakeholder management.','https://www.coursera.org/professional-certificates/google-project-management',31),(36,'Improving Communication Skills','Coursera (University of Pennsylvania)','Practical techniques for persuasive and effective workplace communication.','https://www.coursera.org/learn/wharton-communication-skills',32),(37,'Effective Problem-Solving and Decision-Making','Coursera (UC Irvine)','Structured methods for analysing problems and making better decisions.','https://www.coursera.org/learn/problem-solving',33),(38,'Teamwork Skills: Communicating Effectively in Groups','Coursera (University of Colorado Boulder)','Improve collaboration, group communication and conflict management.','https://www.coursera.org/learn/teamwork-skills-effective-communication',34);
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interview_attempts`
--

DROP TABLE IF EXISTS `interview_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interview_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` text NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `interview_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interview_attempts_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `interview_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interview_attempts`
--

LOCK TABLES `interview_attempts` WRITE;
/*!40000 ALTER TABLE `interview_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `interview_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interview_questions`
--

DROP TABLE IF EXISTS `interview_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interview_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `career_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'easy',
  PRIMARY KEY (`id`),
  KEY `career_id` (`career_id`),
  CONSTRAINT `interview_questions_ibfk_1` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interview_questions`
--

LOCK TABLES `interview_questions` WRITE;
/*!40000 ALTER TABLE `interview_questions` DISABLE KEYS */;
INSERT INTO `interview_questions` VALUES (1,NULL,'Tell me about yourself.','Give a short, structured summary: your current studies or role, key skills and achievements, a relevant project, and why you are interested in this position and company.','easy'),(2,NULL,'Describe a challenging project and how you handled it.','Use the STAR method: describe the Situation, the Task you were responsible for, the Actions you took (planning, teamwork, problem solving) and the Result, including what you learned.','medium'),(3,NULL,'Why should we hire you?','Connect your skills, projects and attitude to the job requirements. Mention specific strengths, evidence of learning quickly, teamwork and how you will add value to the team.','easy'),(4,NULL,'Where do you see yourself in five years?','Show ambition aligned with the company: growing technical skills, taking more responsibility, mentoring others and contributing to larger projects within the organisation.','easy'),(5,1,'What is the difference between let, const and var in JavaScript?','var is function-scoped and hoisted; let and const are block-scoped. let can be reassigned, const cannot be reassigned after declaration, although objects declared with const can still be mutated.','easy'),(6,1,'Explain the CSS box model.','Every element is a box made of content, padding, border and margin. box-sizing: border-box includes padding and border in the declared width and height, which makes layouts easier.','easy'),(7,1,'How do you make a website responsive?','Use a mobile-first approach with fluid layouts, Flexbox and Grid, relative units, responsive images and media queries, plus the viewport meta tag. Test on different screen sizes.','medium'),(8,1,'What are React hooks and why are they useful?','Hooks such as useState and useEffect let function components manage state and side effects. They make components simpler, reusable through custom hooks and avoid class components.','medium'),(9,1,'How would you improve the performance of a slow web page?','Measure with Lighthouse, then reduce and compress assets, lazy load images, minify and bundle code, use caching and a CDN, avoid layout thrashing and reduce blocking JavaScript.','hard'),(10,2,'What is a REST API?','A REST API exposes resources through URLs and uses HTTP methods such as GET, POST, PUT and DELETE. It is stateless, uses status codes and usually exchanges data in JSON format.','easy'),(11,2,'How do you prevent SQL injection?','Never concatenate user input into queries. Use prepared statements with bound parameters (for example PDO), validate input, apply least privilege database accounts and escape output.','medium'),(12,2,'What is database normalization?','Normalization organises tables to reduce redundancy and improve integrity. First normal form removes repeating groups, second removes partial dependencies, third removes transitive dependencies.','medium'),(13,2,'How should passwords be stored?','Passwords must never be stored in plain text. Store a slow, salted hash using algorithms such as bcrypt or Argon2, for example PHP password_hash, and verify with password_verify.','easy'),(14,2,'How would you scale a backend that is receiving too much traffic?','Profile bottlenecks, add caching, optimise database queries and indexes, use load balancing and horizontal scaling, queues for background jobs and read replicas for the database.','hard'),(15,3,'Explain what happens when you type a URL into the browser.','The browser resolves the domain with DNS, opens a TCP and TLS connection, sends an HTTP request, the server processes it and returns a response, then the browser parses HTML, CSS and JavaScript and renders the page.','medium'),(16,3,'What is the difference between sessions and cookies?','Cookies are stored in the browser and sent with every request. Sessions store data on the server and the browser only keeps a session ID cookie, which is more secure for sensitive data.','easy'),(17,3,'How do the frontend and backend communicate?','Through HTTP requests, usually to a REST or GraphQL API. The frontend sends requests with fetch or forms, and the backend returns JSON or HTML with suitable status codes.','easy'),(18,3,'How would you design authentication for a web application?','Hash passwords, use secure sessions or tokens, regenerate session IDs on login, use HTTPS, protect forms with CSRF tokens, enforce role-based authorization and rate-limit login attempts.','hard'),(19,4,'What is the difference between WHERE and HAVING in SQL?','WHERE filters rows before grouping, while HAVING filters groups after GROUP BY using aggregate functions such as COUNT or SUM.','easy'),(20,4,'How do you handle missing data?','First understand why data is missing, then remove rows, impute values using mean, median or mode, use a separate category or model-based imputation. Document the decision and its impact.','medium'),(21,4,'What is the difference between mean and median?','The mean is the average of all values, while the median is the middle value. The median is more robust when the data contains outliers or is skewed.','easy'),(22,4,'How do you choose the right chart for your data?','Choose based on the message: bar charts compare categories, line charts show trends over time, scatter plots show relationships, histograms show distributions. Keep charts simple and labelled.','medium'),(23,5,'Explain overfitting and how to prevent it.','Overfitting happens when a model learns noise in the training data and performs poorly on new data. Prevent it with cross-validation, more data, regularization, simpler models and early stopping.','medium'),(24,5,'What is the difference between supervised and unsupervised learning?','Supervised learning uses labelled data to predict outputs, such as classification and regression. Unsupervised learning finds patterns in unlabelled data, such as clustering.','easy'),(25,5,'What is a p-value?','A p-value is the probability of seeing results at least as extreme as observed, assuming the null hypothesis is true. A small p-value suggests evidence against the null hypothesis.','medium'),(26,5,'How do you evaluate a classification model?','Use a confusion matrix, accuracy, precision, recall, F1 score and ROC AUC. Choose metrics based on the business problem, especially when classes are imbalanced.','hard'),(27,6,'What is gradient descent?','Gradient descent is an optimization algorithm that updates model parameters in the opposite direction of the gradient of the loss function, using a learning rate, to minimise error.','medium'),(28,6,'What is the bias-variance tradeoff?','Bias is error from overly simple assumptions, variance is error from sensitivity to training data. Increasing model complexity lowers bias but raises variance; the goal is a balance.','medium'),(29,6,'How do you deploy a machine learning model to production?','Package the model and dependencies, often in a Docker container, expose it through an API, monitor predictions and data drift, version models and automate retraining with pipelines.','hard'),(30,6,'What is a neural network?','A neural network is a model made of layers of connected neurons. Each neuron applies weights, a bias and an activation function; the network learns weights using backpropagation.','easy'),(31,7,'What is CI/CD?','Continuous Integration automatically builds and tests code whenever changes are merged. Continuous Delivery or Deployment automatically releases tested code to staging or production.','easy'),(32,7,'What is the difference between a container and a virtual machine?','A virtual machine includes a full guest operating system on a hypervisor. A container shares the host kernel and packages only the application and dependencies, so it is lighter and faster to start.','medium'),(33,7,'What is Infrastructure as Code?','Infrastructure as Code manages servers and cloud resources using version-controlled configuration files, with tools such as Terraform or Ansible, making environments repeatable and automated.','medium'),(34,7,'How would you handle a production outage?','Communicate with stakeholders, check monitoring and logs, roll back or mitigate quickly, find the root cause, fix it, then write a blameless post-mortem and add alerts to prevent recurrence.','hard'),(35,8,'What is the CIA triad?','Confidentiality, Integrity and Availability. Confidentiality protects data from unauthorised access, integrity ensures data is accurate and unaltered, availability keeps systems accessible.','easy'),(36,8,'What is the difference between symmetric and asymmetric encryption?','Symmetric encryption uses one shared key for encryption and decryption, such as AES. Asymmetric encryption uses a public and private key pair, such as RSA, often for key exchange.','medium'),(37,8,'How would you respond to a phishing incident?','Identify affected users, isolate compromised accounts or devices, reset credentials, block malicious domains, analyse logs, remove the email, inform users and document lessons learned.','hard'),(38,8,'What is a firewall?','A firewall monitors and filters network traffic based on security rules, allowing or blocking connections to protect a network from unauthorised access.','easy'),(39,9,'What is the difference between UI and UX?','UX is the overall experience and usability of a product, based on research and user needs. UI is the visual interface: layout, colours, typography and interactive elements.','easy'),(40,9,'Describe your design process.','Research users, define problems and personas, ideate, create wireframes and prototypes, run usability testing, iterate based on feedback and hand off designs to developers.','medium'),(41,9,'How do you design for accessibility?','Use sufficient colour contrast, readable fonts, keyboard navigation, alt text, clear labels and focus states, and follow WCAG guidelines. Test with assistive technology.','medium'),(42,9,'How do you handle negative feedback on your design?','Listen without being defensive, understand the reasoning, connect feedback to user goals and data, test alternatives and iterate. Feedback improves the product.','easy'),(43,10,'Explain the Android activity lifecycle.','An activity moves through onCreate, onStart, onResume, onPause, onStop and onDestroy. Developers save state and release resources in the correct lifecycle methods.','medium'),(44,10,'What is the difference between native and cross-platform development?','Native apps use platform languages such as Kotlin or Swift for best performance. Cross-platform frameworks such as Flutter share one codebase for Android and iOS, saving time.','easy'),(45,10,'How do you make a mobile app work offline?','Cache data locally using a database such as SQLite or Room, queue changes and sync with the server when the connection returns, and handle conflicts gracefully.','hard'),(46,10,'What are the four principles of object-oriented programming?','Encapsulation, abstraction, inheritance and polymorphism. They help organise code into reusable, maintainable classes and objects.','easy'),(47,11,'What is the difference between verification and validation?','Verification checks that the product is built correctly according to specifications (are we building it right). Validation checks that it meets user needs (are we building the right product).','medium'),(48,11,'What makes a good bug report?','A clear title, steps to reproduce, expected and actual results, environment details, severity, priority and screenshots or logs.','easy'),(49,11,'What are unit, integration and system testing?','Unit testing checks individual functions, integration testing checks modules working together, and system testing checks the complete application against requirements.','easy'),(50,11,'When should a test be automated?','Automate tests that are repetitive, stable, run often such as regression tests, or are data-driven. Keep exploratory and usability testing manual.','medium'),(51,12,'How do you gather requirements?','Identify stakeholders, then use interviews, workshops, surveys, observation and document analysis. Record requirements as user stories or use cases and validate them with stakeholders.','easy'),(52,12,'What is a SWOT analysis?','SWOT analyses Strengths, Weaknesses, Opportunities and Threats to support strategic decisions about a business, product or project.','easy'),(53,12,'How do you handle conflicting stakeholder requirements?','Understand each stakeholder goal, find common objectives, use data and prioritisation techniques such as MoSCoW, facilitate discussion and document agreed decisions.','hard'),(54,12,'What is the difference between functional and non-functional requirements?','Functional requirements describe what the system should do. Non-functional requirements describe quality attributes such as performance, security, usability and reliability.','medium');
/*!40000 ALTER TABLE `interview_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `technologies` text DEFAULT NULL,
  `project_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,3,'Library Management System','Web application to manage books, members and borrowing records.','PHP, MySQL, HTML, CSS',NULL,'2026-10-03 11:24:47'),(2,3,'Weather Dashboard','Responsive dashboard that shows live weather using a public REST API.','JavaScript, REST API, CSS',NULL,'2026-10-03 11:24:47');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recommendation_history`
--

DROP TABLE IF EXISTS `recommendation_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommendation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `career_id` int(11) NOT NULL,
  `compatibility_score` decimal(5,2) NOT NULL,
  `explanation` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `career_id` (`career_id`),
  CONSTRAINT `recommendation_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recommendation_history_ibfk_2` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recommendation_history`
--

LOCK TABLES `recommendation_history` WRITE;
/*!40000 ALTER TABLE `recommendation_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `recommendation_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skills`
--

DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skills`
--

LOCK TABLES `skills` WRITE;
/*!40000 ALTER TABLE `skills` DISABLE KEYS */;
INSERT INTO `skills` VALUES (1,'HTML & CSS','Web Development'),(2,'JavaScript','Programming'),(3,'React','Web Development'),(4,'Responsive Design','Web Development'),(5,'PHP','Programming'),(6,'Python','Programming'),(7,'Java','Programming'),(8,'Kotlin','Mobile Development'),(9,'Flutter/Dart','Mobile Development'),(10,'SQL','Database'),(11,'Database Design','Database'),(12,'REST APIs','Web Development'),(13,'Object-Oriented Programming','Computer Science'),(14,'Data Structures & Algorithms','Computer Science'),(15,'Git & Version Control','Tools'),(16,'Linux','Systems'),(17,'Networking','Systems'),(18,'Cloud Computing','Cloud & DevOps'),(19,'Docker','Cloud & DevOps'),(20,'CI/CD','Cloud & DevOps'),(21,'Cybersecurity Fundamentals','Security'),(22,'Statistics','Data'),(23,'Excel','Data'),(24,'Data Visualization','Data'),(25,'Machine Learning','Artificial Intelligence'),(26,'Deep Learning','Artificial Intelligence'),(27,'UI/UX Design','Design'),(28,'Figma','Design'),(29,'Software Testing','Quality Assurance'),(30,'Business Analysis','Business'),(31,'Project Management','Business'),(32,'Communication','Soft Skills'),(33,'Problem Solving','Soft Skills'),(34,'Teamwork','Soft Skills');
/*!40000 ALTER TABLE `skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `university` varchar(150) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profiles`
--

LOCK TABLES `student_profiles` WRITE;
/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
INSERT INTO `student_profiles` VALUES (1,3,'Sample University','Computer Science and Engineering','7th Semester',3.45,'Web Development, Data, Artificial Intelligence','Final-year CSE student who enjoys building web applications.');
/*!40000 ALTER TABLE `student_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_skills`
--

DROP TABLE IF EXISTS `student_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_skill` (`user_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  CONSTRAINT `student_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_skills`
--

LOCK TABLES `student_skills` WRITE;
/*!40000 ALTER TABLE `student_skills` DISABLE KEYS */;
INSERT INTO `student_skills` VALUES (1,3,1,'advanced'),(2,3,2,'intermediate'),(3,3,5,'intermediate'),(4,3,10,'intermediate'),(5,3,15,'beginner'),(6,3,6,'beginner'),(7,3,33,'intermediate'),(8,3,32,'intermediate');
/*!40000 ALTER TABLE `student_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','advisor','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified_at` datetime DEFAULT NULL,
  `verification_token_hash` char(64) DEFAULT NULL,
  `verification_expires_at` datetime DEFAULT NULL,
  `verification_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `verification_token_hash` (`verification_token_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Admin','admin@careernavigator.com','$2y$10$JiN6ysYNySwpoyeuiX.wOuUi5PD.9b/7mgCbqCeEQmtZcv6Y6jzmu','admin','2026-10-03 11:24:47','2026-10-03 11:24:47',NULL,NULL,NULL),(2,'Career Advisor','advisor@careernavigator.com','$2y$10$Bncdp7ER3WBfmRxxHAI/5.FXqClZHVQqB0KdiFKSytdbQggsbON6K','advisor','2026-10-03 11:24:47','2026-10-03 11:24:47',NULL,NULL,NULL),(3,'Demo Student','student@careernavigator.com','$2y$10$7Z7wGm4LR47aHYGGN/4opeAXruNvxV.nzLZAGUIQGU.FYyuPkDaR2','student','2026-10-03 11:24:47','2026-10-03 11:24:47',NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'careernavigator'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-03 17:42:22
