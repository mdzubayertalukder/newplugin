# Database Collation Fix

## Issue
The application was experiencing database errors when trying to join the `tl_saas_accounts` and `tenants` tables:

```
SQLSTATE[HY000]: General error: 1267 Illegal mix of collations (utf8mb4_unicode_ci,IMPLICIT) and (utf8mb4_general_ci,IMPLICIT) for operation '='
```

## Root Cause
The error occurs because the `tl_saas_accounts.tenant_id` and `tenants.id` columns have different collations:
- One uses `utf8mb4_unicode_ci`
- The other uses `utf8mb4_general_ci`

When MySQL tries to join these columns, it cannot compare them directly due to the collation mismatch.

## Solutions Implemented

### 1. Code-Level Fix (Immediate)
Modified the `PlanLimitsController::usage()` method to avoid the problematic join by:
- Querying `tl_saas_accounts` separately
- Querying `tenants` separately for each tenant ID
- Combining the results in PHP instead of using SQL JOIN

**File:** `plugins/dropshipping/src/Http/Controllers/Admin/PlanLimitsController.php`

### 2. Database-Level Fix (Permanent)
Created a SQL script to standardize collations across all relevant tables.

**File:** `plugins/dropshipping/fix_collation.sql`

## How to Apply Database Fix

1. **Backup your database first!**
2. Run the collation check query to see current collations:
   ```sql
   SELECT 
       TABLE_NAME,
       COLUMN_NAME,
       COLLATION_NAME
   FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
       AND TABLE_NAME IN ('tl_saas_accounts', 'tenants', 'tl_saas_packages')
       AND COLUMN_NAME IN ('id', 'tenant_id', 'package_id');
   ```

3. Apply the collation fixes:
   ```sql
   ALTER TABLE `tl_saas_accounts` 
   MODIFY COLUMN `tenant_id` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   
   ALTER TABLE `tenants` 
   MODIFY COLUMN `id` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. Verify the changes by running the check query again.

## Testing
After applying the fix, test the following functionality:
- View usage statistics for packages
- Edit package limits
- Create new package limits
- All dropdown actions in the plan limits interface

## Prevention
To prevent similar issues in the future:
- Ensure all new tables use consistent collation (`utf8mb4_unicode_ci`)
- Review table schemas before creating foreign key relationships
- Test joins between tables during development

## Error Handling
The code now includes comprehensive error handling:
- Graceful fallback when database queries fail
- Logging of specific errors for debugging
- Continuation of processing even if individual tenants fail
- User-friendly error messages

## Files Modified
- `plugins/dropshipping/src/Http/Controllers/Admin/PlanLimitsController.php`
- `plugins/dropshipping/fix_collation.sql` (new)
- `plugins/dropshipping/COLLATION_FIX.md` (new) 