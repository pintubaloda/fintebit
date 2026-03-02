<?php
require_once 'includes/db.php';

// Create tables
$tables = [
"CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    description TEXT,
    category VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0.00,
    is_free TINYINT(1) DEFAULT 0,
    level ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
    duration VARCHAR(50),
    lessons INT DEFAULT 0,
    total_lessons INT DEFAULT 0,
    instructor VARCHAR(100),
    image_color VARCHAR(20) DEFAULT '#4f46e5',
    icon VARCHAR(50) DEFAULT 'fas fa-book',
    rating DECIMAL(3,1) DEFAULT 4.5,
    students INT DEFAULT 0,
    students_count INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    course_id INT,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (user_id, course_id)
)",
"CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    title VARCHAR(200),
    content TEXT,
    duration VARCHAR(20),
    order_num INT DEFAULT 0,
    is_preview TINYINT(1) DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending','completed','failed') DEFAULT 'completed',
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    course_id INT,
    rating INT DEFAULT 5,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)"
];

foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        echo "Error: " . $conn->error . "<br>";
    }
}

// Insert admin user
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO users (name, email, password, role) VALUES ('Admin User', 'admin@fintebit.com', '$adminPass', 'admin')");

// Insert demo user
$userPass = password_hash('user123', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO users (name, email, password, role) VALUES ('John Doe', 'user@fintebit.com', '$userPass', 'user')");

// Insert 20 courses
$courses = [
    ["Microsoft Excel Mastery", "Master Excel from basics to advanced: formulas, pivot tables, macros, and data analysis.", "Spreadsheet", 49.99, 0, "Beginner", "12 hours", 45, "Sarah Johnson", "#217346", "fas fa-file-excel", 4.8, 12450],
    ["HTML5 Complete Guide", "Build modern websites with HTML5 semantic elements, forms, multimedia, and accessibility.", "Web Dev", 0.00, 1, "Beginner", "8 hours", 32, "Mike Chen", "#e34c26", "fab fa-html5", 4.7, 28900],
    ["CSS3 & Animations", "Style beautiful websites with CSS3, Flexbox, Grid, transitions, and animations.", "Web Dev", 39.99, 0, "Beginner", "10 hours", 38, "Emma Davis", "#264de4", "fab fa-css3-alt", 4.6, 19200],
    ["Java Programming", "Complete Java course: OOP, data structures, algorithms, and enterprise development.", "Programming", 59.99, 0, "Intermediate", "20 hours", 72, "Dr. Raj Patel", "#f89820", "fab fa-java", 4.9, 8700],
    ["Artificial Intelligence", "Introduction to AI: machine learning, neural networks, NLP, and real-world applications.", "AI/ML", 79.99, 0, "Advanced", "25 hours", 85, "Dr. Lisa Wang", "#ff6b6b", "fas fa-robot", 4.9, 6300],
    ["Python for Beginners", "Learn Python programming from scratch with hands-on projects and real examples.", "Programming", 0.00, 1, "Beginner", "15 hours", 55, "Alex Turner", "#3572A5", "fab fa-python", 4.8, 45600],
    ["JavaScript ES6+", "Modern JavaScript: ES6+, async/await, DOM, APIs, and full-stack development basics.", "Web Dev", 54.99, 0, "Intermediate", "18 hours", 65, "Chris Martinez", "#f7df1e", "fab fa-js", 4.7, 22100],
    ["Data Science with Python", "Data analysis, visualization, pandas, numpy, and machine learning with Python.", "Data Science", 89.99, 0, "Intermediate", "30 hours", 95, "Dr. Amy Lin", "#4584b6", "fas fa-chart-bar", 4.8, 9800],
    ["React.js Development", "Build dynamic web apps with React, hooks, Redux, and modern frontend architecture.", "Web Dev", 69.99, 0, "Intermediate", "22 hours", 78, "Sam Wilson", "#61DAFB", "fab fa-react", 4.8, 15400],
    ["MySQL Database", "Database design, SQL queries, optimization, stored procedures, and administration.", "Database", 0.00, 1, "Beginner", "10 hours", 40, "Kevin Brown", "#00758f", "fas fa-database", 4.6, 31200],
    ["Machine Learning A-Z", "Supervised, unsupervised learning, deep learning, and ML project deployment.", "AI/ML", 99.99, 0, "Advanced", "35 hours", 110, "Prof. James Hall", "#ff9500", "fas fa-brain", 4.9, 7200],
    ["Cybersecurity Basics", "Network security, ethical hacking, cryptography, and cybersecurity best practices.", "Security", 64.99, 0, "Intermediate", "16 hours", 58, "Ryan Scott", "#2d3436", "fas fa-shield-alt", 4.7, 5600],
    ["Node.js & Express", "Server-side JavaScript, REST APIs, authentication, and database integration.", "Web Dev", 59.99, 0, "Intermediate", "20 hours", 70, "Tina Lee", "#68a063", "fab fa-node-js", 4.7, 11300],
    ["UI/UX Design", "Design principles, wireframing, prototyping, Figma, and user research methodologies.", "Design", 0.00, 1, "Beginner", "12 hours", 48, "Olivia Park", "#ff7eb3", "fas fa-palette", 4.6, 18900],
    ["Cloud Computing AWS", "AWS fundamentals, EC2, S3, Lambda, and cloud architecture best practices.", "Cloud", 84.99, 0, "Intermediate", "24 hours", 80, "Daniel Kim", "#FF9900", "fab fa-aws", 4.8, 8400],
    ["PHP & Laravel", "Backend web development with PHP, Laravel framework, MVC, and API development.", "Web Dev", 54.99, 0, "Intermediate", "22 hours", 75, "Frank Garcia", "#777BB4", "fab fa-php", 4.6, 7800],
    ["Digital Marketing", "SEO, social media marketing, Google Ads, analytics, and growth hacking strategies.", "Marketing", 0.00, 1, "Beginner", "10 hours", 38, "Natalie Ross", "#ea4335", "fas fa-bullhorn", 4.5, 24500],
    ["TypeScript Deep Dive", "TypeScript from basics to advanced: types, interfaces, generics, and best practices.", "Programming", 49.99, 0, "Intermediate", "14 hours", 52, "Bob White", "#3178c6", "fas fa-code", 4.7, 9100],
    ["Data Structures & Algo", "Master DSA for coding interviews: arrays, trees, graphs, and algorithmic thinking.", "Programming", 74.99, 0, "Advanced", "28 hours", 92, "Prof. Karen Yu", "#6c5ce7", "fas fa-sitemap", 4.9, 13700],
    ["Excel VBA & Macros", "Automate Excel with VBA programming, macros, user forms, and workflow automation.", "Spreadsheet", 44.99, 0, "Intermediate", "14 hours", 50, "Tom Clark", "#1d6f42", "fas fa-cogs", 4.7, 8200],
];

$conn->query("DELETE FROM courses");
foreach ($courses as $c) {
    $slug = strtolower(trim($c[0]));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    $stmt = $conn->prepare("INSERT INTO courses (title, slug, description, category, price, is_free, level, duration, lessons, total_lessons, instructor, image_color, icon, rating, students, students_count, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssssdissiisssdii", $c[0], $slug, $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], $c[7], $c[7], $c[8], $c[9], $c[10], $c[11], $c[12], $c[12]);
    $stmt->execute();
}

// Insert sample lessons for first course
$conn->query("DELETE FROM lessons");
$sampleLessons = [
    [1, "Introduction to Excel Interface", "Learn the Excel interface, ribbons, and basic navigation.", "15 min", 1],
    [1, "Working with Cells and Data", "Enter, edit, and format data in cells effectively.", "20 min", 2],
    [1, "Basic Formulas and Functions", "SUM, AVERAGE, COUNT, and essential formulas.", "25 min", 3],
    [1, "Charts and Visualizations", "Create professional charts and graphs.", "30 min", 4],
    [1, "Pivot Tables Mastery", "Analyze data with powerful pivot tables.", "35 min", 5],
];

foreach ($sampleLessons as $l) {
    $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content, duration, order_num) VALUES (?,?,?,?,?)");
    $stmt->bind_param("isssi", $l[0], $l[1], $l[2], $l[3], $l[4]);
    $stmt->execute();
}

echo "<div style='font-family:sans-serif;padding:40px;background:#0f172a;color:#fff;min-height:100vh'>
<h1 style='color:#6366f1'>✅ Fintebit Setup Complete!</h1>
<p>Database and tables created successfully.</p>
<h3>Login Credentials:</h3>
<table style='border-collapse:collapse;width:400px'>
<tr style='background:#1e293b'><th style='padding:10px;text-align:left'>Role</th><th style='padding:10px;text-align:left'>Email</th><th style='padding:10px;text-align:left'>Password</th></tr>
<tr><td style='padding:10px'>Admin</td><td style='padding:10px'>admin@fintebit.com</td><td style='padding:10px'>admin123</td></tr>
<tr><td style='padding:10px'>User</td><td style='padding:10px'>user@fintebit.com</td><td style='padding:10px'>user123</td></tr>
</table>
<br><a href='index.php' style='background:#6366f1;color:#fff;padding:12px 24px;text-decoration:none;border-radius:8px'>Go to Homepage →</a>
</div>";
?>
