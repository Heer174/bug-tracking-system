-- ==========================================================
-- Bug Tracking System (BugTrace) - Database Schema & Seed Data
-- Target Database: MySQL / MariaDB (XAMPP compatible)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `bug_tracking_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bug_tracking_system`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `bug_history`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `bugs`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'developer', 'tester') NOT NULL DEFAULT 'tester',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: projects
-- --------------------------------------------------------
CREATE TABLE `projects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: bugs
-- --------------------------------------------------------
CREATE TABLE `bugs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `project_id` INT NOT NULL,
  `reporter_id` INT NOT NULL,
  `assigned_to` INT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'UI / Layout',
  `priority` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
  `severity` ENUM('Low', 'Minor', 'Major', 'Critical', 'Blocker') NOT NULL DEFAULT 'Major',
  `status` ENUM('New', 'Assigned', 'In Progress', 'Fixed', 'Verified', 'Reopened', 'Closed') NOT NULL DEFAULT 'New',
  `steps_to_reproduce` TEXT NULL,
  `expected_result` TEXT NULL,
  `actual_result` TEXT NULL,
  `screenshot` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bugs_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bugs_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_bugs_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_bugs_status` (`status`),
  INDEX `idx_bugs_priority` (`priority`),
  INDEX `idx_bugs_project` (`project_id`),
  INDEX `idx_bugs_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: comments
-- --------------------------------------------------------
CREATE TABLE `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bug_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comments_bug` FOREIGN KEY (`bug_id`) REFERENCES `bugs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_comments_bug` (`bug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: bug_history
-- --------------------------------------------------------
CREATE TABLE `bug_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bug_id` INT NOT NULL,
  `changed_by` INT NOT NULL,
  `previous_status` VARCHAR(50) NOT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `change_description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_history_bug` FOREIGN KEY (`bug_id`) REFERENCES `bugs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_history_bug` (`bug_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA FOR DEMONSTRATION & ACADEMIC EVALUATION
-- ==========================================================

-- Default Users:
-- Admin: admin@bugtrace.com (Password: admin123)
-- Developer 1: alex.dev@bugtrace.com (Password: dev123)
-- Developer 2: sarah.dev@bugtrace.com (Password: dev123)
-- Tester 1: mike.tester@bugtrace.com (Password: tester123)
-- Tester 2: emma.tester@bugtrace.com (Password: tester123)

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Eleanor Vance (Admin)', 'admin@bugtrace.com', '$2y$10$BXv0Qs3.y9Qsic5.IoFgjOCub1MXUDwr40r90V5S6c6ei/IKFyQOu', 'admin', 'active', '2026-09-01 08:30:00'),
(2, 'Alex Rivera (Dev)', 'alex.dev@bugtrace.com', '$2y$10$XluZZNbB1nj3y0rqIU425eZrzlqnjz0T.79zluPTpY3xGSW6nLzIe', 'developer', 'active', '2026-09-01 09:00:00'),
(3, 'Sarah Chen (Dev)', 'sarah.dev@bugtrace.com', '$2y$10$XluZZNbB1nj3y0rqIU425eZrzlqnjz0T.79zluPTpY3xGSW6nLzIe', 'developer', 'active', '2026-09-01 09:15:00'),
(4, 'Michael Chang (QA)', 'mike.tester@bugtrace.com', '$2y$10$QQFhiiV2pRt0dh.Sd7CHdu.m484omGLjq2r36.QhjC.9ktQ/E7RFy', 'tester', 'active', '2026-09-02 10:00:00'),
(5, 'Emma Watson (QA)', 'emma.tester@bugtrace.com', '$2y$10$QQFhiiV2pRt0dh.Sd7CHdu.m484omGLjq2r36.QhjC.9ktQ/E7RFy', 'tester', 'active', '2026-09-02 10:30:00');

-- Projects
INSERT INTO `projects` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'E-Commerce Web Portal', 'Full-stack online retail storefront with product catalog, cart, and payment gateway.', '2026-09-05 10:00:00'),
(2, 'Mobile Banking Application', 'Cross-platform financial mobile app handling transfers, accounts, and biometric authentication.', '2026-09-05 10:15:00'),
(3, 'Internal ERP & HR System', 'Enterprise resource planning software for employee attendance, payroll, and asset management.', '2026-09-06 11:00:00');

-- Realistic Bugs covering all statuses
INSERT INTO `bugs` (`id`, `title`, `description`, `project_id`, `reporter_id`, `assigned_to`, `category`, `priority`, `severity`, `status`, `steps_to_reproduce`, `expected_result`, `actual_result`, `screenshot`, `created_at`, `updated_at`) VALUES
(1, 'Payment Gateway Timeout during 3D Secure verification', 'When users submit Visa / Mastercard payments, the callback handler hangs indefinitely and returns a 504 Gateway error if 3D Secure takes over 15 seconds.', 1, 4, 2, 'Backend / API', 'Critical', 'Blocker', 'In Progress', 
'1. Add items to shopping cart.\n2. Proceed to Checkout and enter card details.\n3. Trigger OTP confirmation.\n4. Wait 15+ seconds before entering OTP.\n5. Click Confirm.', 
'The transaction should either complete successfully or fail gracefully with a retry prompt.', 
'A blank 504 Gateway Timeout page is rendered, and customer card is placed in a hold state.', 
NULL, '2026-09-10 11:20:00', '2026-09-11 14:10:00'),

(2, 'Broken checkout modal layout on mobile Safari iOS 17', 'Checkout modal buttons overlap with the navigation footer bar on iPhone 14/15 running Safari.', 1, 5, 3, 'UI / Layout', 'High', 'Major', 'Fixed', 
'1. Open website on iPhone Safari.\n2. Tap Cart icon.\n3. Click Proceed to Checkout.\n4. Scroll to bottom of payment options.', 
'Complete Order button should remain fully visible and clickable above bottom navigation bar.', 
'The button is partially obscured by the bottom sticky nav bar, preventing checkout.', 
NULL, '2026-09-12 09:40:00', '2026-09-13 16:30:00'),

(3, 'Biometric TouchID login bypass on app backgrounding', 'Suspending the app during fingerprint authentication and reopening it bypasses the PIN lock screen under specific conditions.', 2, 4, 2, 'Security', 'Critical', 'Critical', 'Assigned', 
'1. Launch app on biometric-enabled test device.\n2. Prompt for fingerprint.\n3. Swipe up to multitask.\n4. Wait 5 seconds and return to app.', 
'App should re-verify credentials and prompt for master PIN.', 
'User dashboard is displayed without requiring biometric confirmation.', 
NULL, '2026-09-14 14:00:00', '2026-09-14 15:30:00'),

(4, 'SQL syntax exception in HR monthly payroll export', 'Exporting payroll reports with date ranges containing single quotes triggers an uncaught PDO database exception.', 3, 5, NULL, 'Database', 'High', 'Major', 'New', 
'1. Navigate to HR Portal -> Payroll Reports.\n2. Enter custom date filter.\n3. Click Export to Excel.', 
'An Excel (.xlsx) file should download cleanly.', 
'HTTP 500 error page with database query syntax trace.', 
NULL, '2026-09-15 10:15:00', '2026-09-15 10:15:00'),

(5, 'User profile avatar upload fails silently for WebP images', 'Uploading .webp format avatars returns an undefined error notification without updating profile picture.', 1, 4, 3, 'UI / Layout', 'Medium', 'Minor', 'Verified', 
'1. Go to Account Settings -> Profile.\n2. Select sample.webp avatar.\n3. Click Save Changes.', 
'Avatar updates with preview and success toast message.', 
'Page reloads with old avatar image; no feedback given to user.', 
NULL, '2026-09-08 13:00:00', '2026-09-10 16:45:00'),

(6, 'Push notification token registration returns HTTP 403', 'FCM registration endpoint denies client requests following token refresh cycle.', 2, 5, 2, 'Backend / API', 'High', 'Major', 'Reopened', 
'1. Clear app storage.\n2. Login as existing customer.\n3. Grant push notification permission.\n4. Check server logs.', 
'FCM device token saved with HTTP 200 OK.', 
'Response 403 Forbidden: Invalid device authorization header.', 
NULL, '2026-09-07 08:30:00', '2026-09-11 11:00:00'),

(7, 'Memory leak in real-time order tracking WebSocket listener', 'Leaving order tracker page open for over 30 minutes consumes excessive client RAM (>1.5 GB).', 1, 4, 3, 'Performance', 'Medium', 'Major', 'Closed', 
'1. Place test order.\n2. Open Live Order Tracker.\n3. Monitor Chrome DevTools Performance/Memory tab for 30 minutes.', 
'Heap memory should plateau below 80MB.', 
'Heap continually climbs until browser tab crashes.', 
NULL, '2026-09-03 14:10:00', '2026-09-06 17:00:00');

-- Comments
INSERT INTO `comments` (`id`, `bug_id`, `user_id`, `comment_text`, `created_at`) VALUES
(1, 1, 2, 'I am currently profiling the payment gateway cURL timeout handler. It seems the cURL timeout was set to 60s while the reverse proxy drops at 30s.', '2026-09-11 14:15:00'),
(2, 1, 1, 'Please prioritize this fix. We had 4 lost checkout sessions reported today.', '2026-09-11 14:45:00'),
(3, 2, 3, 'Fixed by applying safe-area-inset-bottom padding and increasing z-index of checkout action buttons.', '2026-09-13 16:25:00'),
(4, 5, 3, 'Added webp support in mime validation array and GD resize handler.', '2026-09-09 11:30:00'),
(5, 5, 4, 'Verified on Chrome, Firefox and Edge. Avatars in WebP upload smoothly now.', '2026-09-10 16:40:00'),
(6, 6, 2, 'Updated token validation middleware to accept refreshed bearer tokens.', '2026-09-09 15:00:00'),
(7, 6, 5, 'Reopened: Issue still occurs when the token expires in the background while device is on battery saver mode.', '2026-09-11 11:00:00'),
(8, 7, 3, 'Unsubscribed from socket event listeners in the component unmount and cleanup hooks. Verified stable memory.', '2026-09-06 16:50:00');

-- Bug History / Audit Trail
INSERT INTO `bug_history` (`id`, `bug_id`, `changed_by`, `previous_status`, `new_status`, `change_description`, `created_at`) VALUES
(1, 1, 4, 'None', 'New', 'Bug reported by Michael Chang', '2026-09-10 11:20:00'),
(2, 1, 1, 'New', 'Assigned', 'Assigned to developer Alex Rivera by Eleanor Vance', '2026-09-10 13:00:00'),
(3, 1, 2, 'Assigned', 'In Progress', 'Alex Rivera started working on timeout reproduction', '2026-09-11 14:10:00'),

(4, 2, 5, 'None', 'New', 'Bug reported by Emma Watson', '2026-09-12 09:40:00'),
(5, 2, 1, 'New', 'Assigned', 'Assigned to Sarah Chen', '2026-09-12 11:00:00'),
(6, 2, 3, 'Assigned', 'In Progress', 'Sarah Chen began styling adjustments', '2026-09-12 14:00:00'),
(7, 2, 3, 'In Progress', 'Fixed', 'Sarah Chen implemented safe area fixes and submitted for verification', '2026-09-13 16:30:00'),

(8, 3, 4, 'None', 'New', 'Bug reported by Michael Chang', '2026-09-14 14:00:00'),
(9, 3, 1, 'New', 'Assigned', 'Assigned to Alex Rivera with High priority', '2026-09-14 15:30:00'),

(10, 4, 5, 'None', 'New', 'Bug reported by Emma Watson', '2026-09-15 10:15:00'),

(11, 5, 4, 'None', 'New', 'Bug reported by Michael Chang', '2026-09-08 13:00:00'),
(12, 5, 1, 'New', 'Assigned', 'Assigned to Sarah Chen', '2026-09-08 14:00:00'),
(13, 5, 3, 'Assigned', 'In Progress', 'Sarah Chen working on image conversion', '2026-09-09 10:00:00'),
(14, 5, 3, 'In Progress', 'Fixed', 'Sarah Chen marked as fixed', '2026-09-09 11:35:00'),
(15, 5, 4, 'Fixed', 'Verified', 'Michael Chang tested and verified avatar upload', '2026-09-10 16:45:00'),

(16, 6, 5, 'None', 'New', 'Bug reported by Emma Watson', '2026-09-07 08:30:00'),
(17, 6, 1, 'New', 'Assigned', 'Assigned to Alex Rivera', '2026-09-07 09:30:00'),
(18, 6, 2, 'Assigned', 'In Progress', 'Alex Rivera investigating auth header format', '2026-09-08 10:00:00'),
(19, 6, 2, 'In Progress', 'Fixed', 'Alex Rivera deployed bearer token parsing update', '2026-09-09 15:05:00'),
(20, 6, 5, 'Fixed', 'Reopened', 'Emma Watson found token expiry still triggers 403 on battery saver', '2026-09-11 11:00:00'),

(21, 7, 4, 'None', 'New', 'Bug reported by Michael Chang', '2026-09-03 14:10:00'),
(22, 7, 1, 'New', 'Assigned', 'Assigned to Sarah Chen', '2026-09-03 15:00:00'),
(23, 7, 3, 'Assigned', 'In Progress', 'Sarah Chen profiling socket heap memory', '2026-09-04 11:00:00'),
(24, 7, 3, 'In Progress', 'Fixed', 'Sarah Chen cleared dangling event listeners', '2026-09-06 16:55:00'),
(25, 7, 4, 'Fixed', 'Verified', 'Michael Chang confirmed memory stability over 60 min test', '2026-09-06 17:15:00'),
(26, 7, 1, 'Verified', 'Closed', 'Eleanor Vance closed bug ticket', '2026-09-06 17:30:00');
