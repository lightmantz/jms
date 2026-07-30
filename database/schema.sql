-- Create database
CREATE DATABASE IF NOT EXISTS tirp;
USE tirp;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'reviewer', 'author', 'reader') DEFAULT 'reader',
    institution VARCHAR(255),
    country VARCHAR(100),
    orcid_id VARCHAR(50),
    bio TEXT,
    avatar VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    remember_token VARCHAR(100),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Editorial board table
CREATE TABLE editorial_board (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    position VARCHAR(100),
    affiliation VARCHAR(255),
    biography TEXT,
    expertise VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- Institutions table
CREATE TABLE institutions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    country VARCHAR(100),
    city VARCHAR(100),
    website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Categories/Keywords
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Manuscripts table
CREATE TABLE manuscripts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(500) NOT NULL,
    abstract TEXT,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'submitted', 'under_review', 'revision_required', 'accepted', 'rejected', 'published') DEFAULT 'draft',
    article_type ENUM('original_research', 'review', 'case_report', 'editorial', 'letter', 'commentary', 'other') DEFAULT 'original_research',
    submission_type ENUM('regular', 'special_issue', 'call_for_papers') DEFAULT 'regular',
    special_issue_id INT,
    author_id INT,
    corresponding_author_id INT,
    institution_id INT,
    editor_assigned_id INT,
    doi VARCHAR(100) UNIQUE,
    volume_id INT,
    issue_id INT,
    page_start INT,
    page_end INT,
    publication_date DATE,
    has_conflict_of_interest BOOLEAN DEFAULT FALSE,
    conflicts TEXT,
    funding_source VARCHAR(255),
    acknowledgments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    accepted_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (corresponding_author_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_doi (doi),
    INDEX idx_submission_date (submission_date)
);

-- Manuscript files
CREATE TABLE manuscript_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manuscript_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('manuscript', 'supplementary', 'figures', 'tables', 'other') DEFAULT 'manuscript',
    file_size INT,
    mime_type VARCHAR(100),
    version INT DEFAULT 1,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_manuscript (manuscript_id)
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manuscript_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    editor_id INT,
    invitation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_date TIMESTAMP NULL,
    due_date DATE,
    completed_date TIMESTAMP NULL,
    status ENUM('invited', 'accepted', 'declined', 'completed', 'overdue') DEFAULT 'invited',
    review_type ENUM('single_blind', 'double_blind', 'open_review') DEFAULT 'double_blind',
    recommendation ENUM('accept', 'minor_revision', 'major_revision', 'reject', 'revise_resubmit') NULL,
    comments_to_editor TEXT,
    comments_to_author TEXT,
    confidential_comments TEXT,
    file_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (editor_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_manuscript (manuscript_id),
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_status (status)
);

-- Manuscript revisions
CREATE TABLE revisions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manuscript_id INT NOT NULL,
    user_id INT,
    revision_type ENUM('minor', 'major', 'initial_submission', 'resubmission') DEFAULT 'initial_submission',
    comments TEXT,
    file_path VARCHAR(500),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_manuscript (manuscript_id)
);

-- Volumes
CREATE TABLE volumes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    volume_number INT NOT NULL,
    year INT NOT NULL,
    title VARCHAR(255),
    description TEXT,
    cover_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_volume_year (volume_number, year),
    INDEX idx_volume (volume_number),
    INDEX idx_year (year)
);

-- Issues
CREATE TABLE issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    volume_id INT NOT NULL,
    issue_number INT NOT NULL,
    title VARCHAR(255),
    publication_date DATE,
    description TEXT,
    cover_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (volume_id) REFERENCES volumes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_issue (volume_id, issue_number),
    INDEX idx_volume (volume_id),
    INDEX idx_publication_date (publication_date)
);

-- Article citations (renamed from 'references' to avoid reserved keyword)
CREATE TABLE article_citations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manuscript_id INT NOT NULL,
    author VARCHAR(255),
    title TEXT,
    journal VARCHAR(255),
    year INT,
    volume INT,
    issue INT,
    pages VARCHAR(50),
    doi VARCHAR(100),
    url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    INDEX idx_manuscript (manuscript_id),
    INDEX idx_doi (doi)
);

-- Keywords mapping
CREATE TABLE manuscript_keywords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manuscript_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_manuscript_category (manuscript_id, category_id)
);

-- Notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    link VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

-- Audit logs
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    table_name VARCHAR(100),
    record_id INT,
    old_data TEXT,
    new_data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- Views tracking
CREATE TABLE article_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manuscript_id INT NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_manuscript (manuscript_id),
    INDEX idx_viewed_at (viewed_at)
);

-- Downloads tracking
CREATE TABLE article_downloads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    manuscript_id INT NOT NULL,
    file_id INT,
    user_id INT,
    ip_address VARCHAR(45),
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manuscript_id) REFERENCES manuscripts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_manuscript (manuscript_id),
    INDEX idx_downloaded_at (downloaded_at)
);

-- System settings
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_group, description) VALUES
('site_name', 'TIRP - Tanzania Journal of Rehabilitation Practice', 'general', 'Site name'),
('site_tagline', 'Advancing rehabilitation research in Tanzania', 'general', 'Site tagline'),
('site_email', 'info@lightmantz.com', 'contact', 'Contact email'),
('site_phone', '+255 763 872 771', 'contact', 'Contact phone'),
('site_address', 'P.O. Box 1541, KCMC, Moshi, Tanzania', 'contact', 'Contact address'),
('journal_issn', '1234-5678', 'journal', 'Journal ISSN'),
('journal_frequency', 'Quarterly', 'journal', 'Publication frequency'),
('journal_open_access', 'true', 'journal', 'Open access status'),
('submission_deadline', '2026-09-30', 'journal', 'Current submission deadline'),
('acceptance_rate', '34', 'journal', 'Acceptance rate percentage'),
('avg_turnaround', '28', 'journal', 'Average turnaround days'),
('submissions_current_month', '16', 'journal', 'Submissions this month'),
('total_articles', '120', 'journal', 'Total articles published'),
('total_views_last_month', '8400', 'journal', 'Views last month'),
('editorial_board_size', '45', 'journal', 'Editorial board size');

-- Insert default admin user (password: admin123)
INSERT INTO users (email, password_hash, full_name, role, email_verified, is_active) VALUES
('admin@tirp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', TRUE, TRUE);

-- Insert sample editorial board members
INSERT INTO users (email, password_hash, full_name, role, institution, is_active) VALUES
('editor@tirp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. A. M. Kilonzo', 'editor', 'Kilimanjaro Christian Medical University', TRUE),
('reviewer@tirp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. C. L. Mrema', 'reviewer', 'University of Dar es Salaam', TRUE);

INSERT INTO editorial_board (user_id, position, affiliation, is_active, display_order) VALUES
(2, 'Editor-in-Chief', 'Kilimanjaro Christian Medical University (KCMUCo)', TRUE, 1),
(3, 'Managing Editor', 'University of Dar es Salaam', TRUE, 2);

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Occupational Therapy', 'Research related to occupational therapy practice and theory'),
('Physiotherapy', 'Studies in physical therapy and rehabilitation'),
('Speech Therapy', 'Research in speech and language pathology'),
('Community Rehabilitation', 'Community-based rehabilitation programs and interventions'),
('Disability Studies', 'Research on disability, inclusion, and accessibility'),
('Rehabilitation Technology', 'Assistive technology and rehabilitation engineering'),
('Mental Health', 'Mental health rehabilitation and psychosocial interventions'),
('Pediatric Rehabilitation', 'Rehabilitation for children and adolescents'),
('Geriatric Rehabilitation', 'Rehabilitation for elderly populations'),
('Sports Rehabilitation', 'Sports injury rehabilitation and performance');

-- Insert sample volume and issue
INSERT INTO volumes (volume_number, year, title, is_active) VALUES
(12, 2026, 'Volume 12', TRUE);

INSERT INTO issues (volume_id, issue_number, title, publication_date, is_active, is_current) VALUES
(1, 2, 'Issue 2', '2026-07-14', TRUE, TRUE);