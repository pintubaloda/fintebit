-- Fintebit Online Learning Platform Database
CREATE DATABASE IF NOT EXISTS fintebit;
USE fintebit;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    instructor VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    is_free TINYINT(1) DEFAULT 0,
    duration VARCHAR(50) DEFAULT NULL,
    level ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
    total_lessons INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 4.5,
    students_count INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    duration VARCHAR(20),
    order_num INT DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress INT DEFAULT 0,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending','completed','failed') DEFAULT 'completed',
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- password is 'password' for both accounts
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@fintebit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Doe', 'user@fintebit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO courses (title, slug, description, category, instructor, price, is_free, duration, level, total_lessons, rating, students_count) VALUES
('Microsoft Excel Mastery', 'excel-mastery', 'Master Excel from beginner to advanced. Learn formulas, pivot tables, charts, VBA macros, and data analysis tools used by professionals worldwide.', 'Productivity', 'Sarah Johnson', 0.00, 1, '12 Hours', 'Beginner', 24, 4.8, 15420),
('HTML5 Complete Course', 'html5-complete', 'Build modern websites with HTML5. Cover semantic elements, forms, multimedia, accessibility, and best practices for web development.', 'Web Dev', 'Mike Chen', 0.00, 1, '8 Hours', 'Beginner', 18, 4.7, 22100),
('CSS3 & Modern Styling', 'css3-modern', 'Learn CSS3 animations, Flexbox, Grid, responsive design, and modern styling techniques to create stunning web interfaces.', 'Web Dev', 'Emma Williams', 0.00, 1, '10 Hours', 'Beginner', 20, 4.6, 18350),
('Java Programming Complete', 'java-complete', 'Comprehensive Java programming from fundamentals to OOP, data structures, algorithms, and building real-world applications.', 'Programming', 'Dr. Raj Patel', 1299.00, 0, '40 Hours', 'Intermediate', 65, 4.9, 9800),
('Artificial Intelligence Fundamentals', 'ai-fundamentals', 'Explore AI concepts, machine learning algorithms, neural networks, NLP, and hands-on projects using Python and TensorFlow.', 'AI & ML', 'Dr. Aisha Kumar', 1499.00, 0, '35 Hours', 'Intermediate', 50, 4.9, 7650),
('Python for Data Science', 'python-data-science', 'Master Python for data analysis, visualization, machine learning with pandas, numpy, matplotlib, scikit-learn and more.', 'Data Science', 'Carlos Rodriguez', 999.00, 0, '30 Hours', 'Intermediate', 45, 4.8, 12300),
('JavaScript Modern ES6+', 'javascript-es6', 'Complete JavaScript course covering ES6+, async/await, APIs, DOM manipulation, and modern frameworks introduction.', 'Web Dev', 'Lisa Park', 799.00, 0, '25 Hours', 'Intermediate', 38, 4.7, 14200),
('React.js Development', 'react-development', 'Build powerful web apps with React. Hooks, state management, Redux, routing, and deployment strategies.', 'Web Dev', 'Tom Anderson', 1199.00, 0, '28 Hours', 'Intermediate', 42, 4.8, 8900),
('Machine Learning A-Z', 'machine-learning-az', 'Complete ML course with regression, classification, clustering, NLP, deep learning, and 10 real-world projects.', 'AI & ML', 'Dr. Aisha Kumar', 1799.00, 0, '45 Hours', 'Advanced', 72, 4.9, 6200),
('Web Design with Figma', 'web-design-figma', 'Learn UI/UX design principles and master Figma for creating professional wireframes, prototypes and design systems.', 'Design', 'Sophie Turner', 0.00, 1, '15 Hours', 'Beginner', 22, 4.6, 11500),
('Node.js Backend Development', 'nodejs-backend', 'Build scalable backend applications with Node.js, Express, REST APIs, authentication, and MongoDB database.', 'Backend', 'Mike Chen', 1099.00, 0, '32 Hours', 'Intermediate', 48, 4.7, 7300),
('SQL & Database Design', 'sql-database', 'Master SQL from basics to advanced queries, database design, normalization, and performance optimization techniques.', 'Database', 'Dr. Raj Patel', 0.00, 1, '18 Hours', 'Beginner', 30, 4.8, 19800),
('Cybersecurity Essentials', 'cybersecurity-essentials', 'Learn ethical hacking, network security, cryptography, vulnerability assessment, and security best practices.', 'Security', 'Alex Morrison', 1399.00, 0, '38 Hours', 'Advanced', 55, 4.8, 5400),
('Flutter Mobile Development', 'flutter-mobile', 'Build cross-platform mobile apps for iOS and Android using Flutter and Dart programming language.', 'Mobile Dev', 'Priya Sharma', 1299.00, 0, '35 Hours', 'Intermediate', 52, 4.7, 6800),
('DevOps & Cloud Computing', 'devops-cloud', 'Master Docker, Kubernetes, CI/CD pipelines, AWS, and modern DevOps practices for efficient software delivery.', 'DevOps', 'Carlos Rodriguez', 1599.00, 0, '40 Hours', 'Advanced', 60, 4.8, 4900),
('Excel Advanced Analytics', 'excel-advanced', 'Advanced Excel for business analytics, Power Query, Power Pivot, DAX formulas, and professional dashboards.', 'Productivity', 'Sarah Johnson', 699.00, 0, '20 Hours', 'Advanced', 35, 4.7, 8700),
('PHP & Laravel Framework', 'php-laravel', 'Complete PHP programming and Laravel framework for building robust, scalable web applications with MVC architecture.', 'Backend', 'Emma Williams', 1099.00, 0, '33 Hours', 'Intermediate', 50, 4.6, 6100),
('Digital Marketing & SEO', 'digital-marketing', 'Learn SEO, Google Ads, social media marketing, email campaigns, and analytics to grow your online presence.', 'Marketing', 'Lisa Park', 0.00, 1, '22 Hours', 'Beginner', 32, 4.5, 25000),
('Blockchain & Web3', 'blockchain-web3', 'Understand blockchain technology, smart contracts, Solidity programming, DeFi, and building decentralized apps.', 'Blockchain', 'Alex Morrison', 1699.00, 0, '42 Hours', 'Advanced', 58, 4.7, 3800),
('C++ Programming Masterclass', 'cpp-masterclass', 'Complete C++ from fundamentals to advanced topics: OOP, STL, memory management, game development basics.', 'Programming', 'Tom Anderson', 899.00, 0, '36 Hours', 'Intermediate', 54, 4.6, 7200);

INSERT INTO lessons (course_id, title, content, duration, order_num) VALUES
(1, 'Introduction to Excel Interface', 'Welcome to Excel. Learn the ribbon, quick access toolbar, and navigation.', '15 min', 1),
(1, 'Working with Cells and Data', 'Enter, edit, format data. Cell references and ranges explained.', '20 min', 2),
(1, 'Formulas and Functions', 'SUM, AVERAGE, COUNT, IF, VLOOKUP and more.', '25 min', 3),
(1, 'Charts and Visualization', 'Create bar, pie, line charts and sparklines.', '22 min', 4),
(1, 'Pivot Tables Mastery', 'Summarize large datasets with pivot tables.', '30 min', 5),
(2, 'HTML Introduction', 'What is HTML, document structure, DOCTYPE declaration.', '15 min', 1),
(2, 'HTML Elements and Tags', 'Headings, paragraphs, links, images, lists.', '20 min', 2),
(2, 'Forms and Input Elements', 'Build interactive forms with validation.', '25 min', 3),
(3, 'CSS Fundamentals', 'Selectors, properties, the box model.', '18 min', 1),
(3, 'Flexbox Layout', 'Flexible layouts with Flexbox.', '25 min', 2),
(3, 'CSS Grid System', 'Advanced grid-based layouts.', '28 min', 3),
(4, 'Java Basics', 'Variables, data types, operators in Java.', '20 min', 1),
(4, 'OOP Concepts', 'Classes, objects, inheritance, polymorphism.', '30 min', 2),
(5, 'What is AI?', 'History and foundations of Artificial Intelligence.', '18 min', 1),
(5, 'Machine Learning Overview', 'Supervised, unsupervised, reinforcement learning.', '25 min', 2);
