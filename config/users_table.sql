-- ==========================================
-- SMART EVENT PLANNER + BUDGET TRACKER
-- Complete Database Schema
-- ==========================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS vendor_categories;
DROP TABLE IF EXISTS timeline_milestones;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS ai_templates;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- USERS
-- ==========================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================
-- EVENTS
-- ==========================================

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,

    event_name VARCHAR(200) NOT NULL,

    event_type ENUM(
        'Wedding',
        'Birthday Party',
        'Corporate Event',
        'Baby Shower',
        'Graduation',
        'Custom'
    ) NOT NULL,

    custom_event_type VARCHAR(100) NULL,

    event_date DATE NOT NULL,

    guest_count INT DEFAULT 0,

    venue_name VARCHAR(255) NULL,

    location VARCHAR(255) NULL,

    total_budget DECIMAL(12,2) DEFAULT 0,

    currency ENUM('PKR','USD') DEFAULT 'PKR',

    description TEXT NULL,

    status ENUM(
        'Planning',
        'In Progress',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Planning',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_events_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
);

-- ==========================================
-- TASKS
-- ==========================================

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,

    event_id INT NOT NULL,

    task_name VARCHAR(255) NOT NULL,

    phase ENUM(
        'Pre-Planning',
        'Preparation',
        'Day-Of'
    ) NOT NULL,

    due_date DATE NULL,

    priority ENUM(
        'Low',
        'Medium',
        'High'
    ) DEFAULT 'Medium',

    dependency_task_id INT NULL,

    source ENUM(
        'AI',
        'Manual'
    ) DEFAULT 'AI',

    status ENUM(
        'Pending',
        'Completed',
        'Skipped'
    ) DEFAULT 'Pending',

    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tasks_event
    FOREIGN KEY (event_id)
    REFERENCES events(id)
    ON DELETE CASCADE,

    CONSTRAINT fk_tasks_dependency
    FOREIGN KEY (dependency_task_id)
    REFERENCES tasks(id)
    ON DELETE SET NULL
);

-- ==========================================
-- VENDOR CATEGORIES
-- ==========================================

CREATE TABLE vendor_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,

    event_id INT NOT NULL,

    category_name VARCHAR(150) NOT NULL,

    suggested_percentage DECIMAL(5,2) DEFAULT 0,

    allocated_amount DECIMAL(12,2) DEFAULT 0,

    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_category_event
    FOREIGN KEY (event_id)
    REFERENCES events(id)
    ON DELETE CASCADE
);

-- ==========================================
-- EXPENSES
-- ==========================================

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,

    event_id INT NOT NULL,

    category_id INT NOT NULL,

    vendor_item_name VARCHAR(255) NOT NULL,

    estimated_cost DECIMAL(12,2) DEFAULT 0,

    actual_cost DECIMAL(12,2) DEFAULT 0,

    payment_status ENUM(
        'Paid',
        'Pending',
        'Partially Paid'
    ) DEFAULT 'Pending',

    date_logged DATE NULL,

    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_expense_event
    FOREIGN KEY (event_id)
    REFERENCES events(id)
    ON DELETE CASCADE,

    CONSTRAINT fk_expense_category
    FOREIGN KEY (category_id)
    REFERENCES vendor_categories(id)
    ON DELETE CASCADE
);

-- ==========================================
-- TIMELINE MILESTONES
-- ==========================================

CREATE TABLE timeline_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,

    event_id INT NOT NULL,

    title VARCHAR(255) NOT NULL,

    description TEXT NULL,

    milestone_date DATE NOT NULL,

    status ENUM(
        'Pending',
        'Completed'
    ) DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_timeline_event
    FOREIGN KEY (event_id)
    REFERENCES events(id)
    ON DELETE CASCADE
);

-- ==========================================
-- AI TASK TEMPLATES
-- ==========================================

CREATE TABLE ai_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,

    event_type VARCHAR(100) NOT NULL,

    task_name VARCHAR(255) NOT NULL,

    phase ENUM(
        'Pre-Planning',
        'Preparation',
        'Day-Of'
    ) NOT NULL,

    days_before_event INT NOT NULL,

    priority ENUM(
        'Low',
        'Medium',
        'High'
    ) DEFAULT 'Medium',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- ACTIVITY LOGS
-- ==========================================

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    event_id INT NULL,

    action VARCHAR(255) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

    CONSTRAINT fk_log_event
    FOREIGN KEY (event_id)
    REFERENCES events(id)
    ON DELETE SET NULL
);

-- ==========================================
-- SAMPLE AI TEMPLATES
-- ==========================================

INSERT INTO ai_templates
(event_type, task_name, phase, days_before_event, priority)
VALUES

('Wedding','Book Venue','Pre-Planning',90,'High'),
('Wedding','Hire Photographer','Preparation',60,'High'),
('Wedding','Send Invitations','Preparation',45,'Medium'),
('Wedding','Finalize Catering','Preparation',15,'High'),
('Wedding','Coordinate Vendors','Day-Of',1,'High'),

('Birthday Party','Book Venue','Pre-Planning',30,'High'),
('Birthday Party','Order Cake','Preparation',7,'Medium'),
('Birthday Party','Decorations Setup','Day-Of',1,'High'),

('Corporate Event','Book Conference Hall','Pre-Planning',60,'High'),
('Corporate Event','Invite Guests','Preparation',30,'Medium'),
('Corporate Event','Prepare Presentation','Preparation',7,'High'),

('Baby Shower','Select Theme','Pre-Planning',30,'Medium'),
('Baby Shower','Order Decorations','Preparation',10,'Medium'),

('Graduation','Book Venue','Pre-Planning',45,'High'),
('Graduation','Invite Guests','Preparation',20,'Medium');