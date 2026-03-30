-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS study_planner;
USE study_planner;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    study_level VARCHAR(50) DEFAULT NULL,
    goal VARCHAR(255) DEFAULT NULL,
    study_hours INT DEFAULT NULL,
    pref_time VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: user_settings
CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    focus_session_mins INT DEFAULT 50,
    short_break_mins INT DEFAULT 10,
    daily_limit_hours INT DEFAULT 6,
    daily_reminder BOOLEAN DEFAULT TRUE,
    deadline_alerts BOOLEAN DEFAULT TRUE,
    ai_tips BOOLEAN DEFAULT FALSE,
    school VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: subjects
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(10) DEFAULT '📚',
    topics_count INT DEFAULT 0,
    daily_study_mins INT DEFAULT 30,
    mastery_percent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: tasks
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    duration_mins INT NOT NULL,
    deadline DATETIME NOT NULL,
    status ENUM('pending', 'completed', 'missed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
