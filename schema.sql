```sql
-- ============================================
-- Employee Payroll Database
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS employeer;

-- Select database
USE employeer;


-- ============================================
-- Create employee table
-- ============================================

CREATE TABLE IF NOT EXISTS employee (

    id INT AUTO_INCREMENT PRIMARY KEY,

    BASIC_PAY DECIMAL(12,2) NOT NULL DEFAULT 0,

    DA_PERCENT DECIMAL(8,2) NOT NULL DEFAULT 0,

    DA_AMOUNT DECIMAL(12,2) NOT NULL DEFAULT 0,

    HRA_PERCENT DECIMAL(8,2) NOT NULL DEFAULT 0,

    HRA_AMOUNT DECIMAL(12,2) NOT NULL DEFAULT 0,

    PF_DEDUCTION DECIMAL(12,2) NOT NULL DEFAULT 0,

    ANY_OTHER_ALLOWANCE DECIMAL(12,2) NOT NULL DEFAULT 0,

    TOTAL_PAYMENT DECIMAL(12,2) NOT NULL DEFAULT 0

);
```
