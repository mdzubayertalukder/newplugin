# Package Import Limits Implementation

## Overview

This implementation adds comprehensive package import limits for both **product import** and **product research** functionality. Superadmins can now set limits for different subscription plans, and tenants will be restricted based on their current plan.

## Features

### 🔄 Product Import Limits
- **Monthly Import Limit**: Limit how many products a tenant can import per month
- **Total Import Limit**: Limit total products a tenant can import (lifetime)
- **Bulk Import Limit**: Limit how many products can be imported in a single bulk operation

### 🔍 Product Research Limits
- **Monthly Research Limit**: Limit how many product researches a tenant can perform per month
- **Total Research Limit**: Limit total product researches a tenant can perform (lifetime)
- **Research Usage Tracking**: Track all research activities with detailed logging

### 📊 Usage Tracking
- Real-time usage monitoring
- Detailed statistics and analytics
- Progress bars showing current usage vs limits
- Automatic limit enforcement

### 🚀 Upgrade Messaging
- Clear messaging when limits are reached
- Upgrade plan prompts with specific benefits
- User-friendly error messages

## Database Structure

### Main Database Tables

#### `dropshipping_plan_limits`
```sql
- package_id (FK to tl_saas_packages)
- monthly_import_limit (int, -1 for unlimited)
- total_import_limit (int, -1 for unlimited)
- bulk_import_limit (int, -1 for unlimited)
- monthly_research_limit (int, -1 for unlimited)
- total_research_limit (int, -1 for unlimited)
- auto_sync_enabled (boolean)
- pricing_markup_min/max (decimal)
- settings (JSON)
```

### Tenant Database Tables

#### `dropshipping_research_usage`
```sql
- tenant_id (varchar)
- product_id (bigint)
- product_name (varchar)
- research_type (enum: full_research, price_comparison, seo_analysis, competitor_analysis)
- api_calls_used (int)
- success (boolean)
- error_message (text)
- research_data (JSON)
- researched_at (timestamp)
```

## Implementation Details

### 1. Models

#### `DropshippingPlanLimit`
- Manages package limits configuration
- Provides methods to check if limits are reached
- Calculates remaining usage for tenants

#### `DropshippingResearchUsage`
- Tracks all research activities
- Provides usage statistics
- Supports different research types

### 2. Services

#### `LimitService`
- Central service for all limit-related operations
- Handles limit checking and enforcement
- Provides usage statistics and display formatting
- Manages upgrade messaging

### 3. Controllers

#### Updated `ProductImportController`
- Checks import limits before allowing imports
- Returns detailed error messages with upgrade prompts
- Tracks successful imports

#### Updated `ProductResearchController`
- Checks research limits before allowing research
- Records research usage for tracking
- Provides usage information in responses

#### New `PlanLimitsController` (Admin)
- Manages plan limits configuration
- Provides usage statistics for admin
- Bulk operations for setting up limits

### 4. Frontend Updates

#### Product Import
- Shows upgrade messages when limits reached
- Enhanced error handling with specific limit information

#### Product Research
- Displays current usage information
- Shows upgrade prompts when limits approached
- Real-time limit checking

#### Usage Display
- Progress bars showing current usage
- Color-coded indicators (green/yellow/red)
- Clear upgrade messaging

## Default Limits

### Free Plans
- Monthly Import: 5 products
- Monthly Research: 5 researches
- Bulk Import: 2 products
- Auto Sync: Disabled

### Paid Plans
- Monthly Import: 100 products
- Monthly Research: 100 researches
- Bulk Import: 20 products
- Auto Sync: Enabled

### Basic Plans (Default)
- Monthly Import: 10 products
- Monthly Research: 10 researches
- Bulk Import: 5 products
- Auto Sync: Disabled

## Setup Instructions

### 1. Database Migration
Run the update script to add new tables and columns:
```bash
# Run this SQL script on your main database
mysql -u username -p database_name < plugins/dropshipping/update_plan_limits.sql
```

### 2. Tenant Database Setup
For each tenant database, run:
```sql
-- Create research usage table
CREATE TABLE IF NOT EXISTS `dropshipping_research_usage` (
    -- ... (see full schema in update_plan_limits.sql)
);
```

### 3. Admin Configuration
1. Go to Admin Panel → Dropshipping → Plan Limits
2. Configure limits for each package
3. Set appropriate limits based on your business model

## Usage Examples

### For Tenants

#### Checking Current Usage
```php
use Plugin\Dropshipping\Services\LimitService;

$tenantId = tenant('id');
$usageDisplay = LimitService::getUsageDisplay($tenantId);

// Shows current usage with progress bars
echo $usageDisplay['imports']['monthly']['display']; // "5 / 10"
echo $usageDisplay['research']['monthly']['percentage']; // 50
```

#### Import Limit Check
```php
$canImport = LimitService::canImport($tenantId, 1);

if (!$canImport['allowed']) {
    echo $canImport['message']; // "Monthly import limit reached..."
    echo $canImport['upgrade_message']; // "Upgrade your plan to get more..."
}
```

#### Research Limit Check
```php
$canResearch = LimitService::canResearch($tenantId);

if (!$canResearch['allowed']) {
    echo $canResearch['message']; // "Monthly research limit reached..."
}
```

### For Admins

#### Setting Up Limits
```php
use Plugin\Dropshipping\Models\DropshippingPlanLimit;

DropshippingPlanLimit::create([
    'package_id' => 1,
    'monthly_import_limit' => 50,
    'monthly_research_limit' => 50,
    'bulk_import_limit' => 10,
    // ... other settings
]);
```

#### Getting Usage Statistics
```php
$limits = DropshippingPlanLimit::find(1);
$stats = $limits->getUsageStats($tenantId);

echo $stats['imports']['monthly_imports']; // Current month imports
echo $stats['research']['monthly_research_count']; // Current month research
```

## API Responses

### Import Limit Reached
```json
{
    "success": false,
    "message": "Monthly import limit of 10 products reached. Please upgrade your plan or wait for next month.",
    "reason": "monthly_limit_reached",
    "upgrade_message": "Upgrade your plan to get more imports and research capabilities."
}
```

### Research Limit Reached
```json
{
    "success": false,
    "message": "Monthly research limit of 10 researches reached. Please upgrade your plan or wait for next month.",
    "reason": "monthly_limit_reached",
    "upgrade_message": "Upgrade your plan to get more imports and research capabilities.",
    "limit_reached": true
}
```

### Successful Research with Usage Info
```json
{
    "success": true,
    "data": {
        "search_results": [...],
        "usage_info": {
            "monthly_used": 5,
            "monthly_limit": 10,
            "remaining": 5
        }
    }
}
```

## User Interface

### Tenant Dashboard
- Usage limits widget showing current usage
- Progress bars with color coding
- Upgrade prompts when approaching limits

### Product Import Page
- Real-time limit checking
- Clear error messages with upgrade options
- Bulk import limit enforcement

### Product Research
- Usage information display
- Limit reached warnings
- Upgrade messaging

### Admin Panel
- Plan limits management interface
- Usage statistics and analytics
- Bulk limit configuration tools

## Troubleshooting

### Common Issues

1. **Limits not enforcing**: Check if limits are properly configured in `dropshipping_plan_limits` table
2. **Research tracking not working**: Ensure `dropshipping_research_usage` table exists in tenant databases
3. **Usage statistics incorrect**: Verify tenant package assignment in `tl_saas_accounts`

### Debug Commands

```php
// Check tenant limits
$limits = LimitService::getTenantLimits($tenantId);
var_dump($limits);

// Check usage stats
$stats = LimitService::getUsageStats($tenantId);
var_dump($stats);

// Check package info
$packageInfo = LimitService::getTenantPackageInfo($tenantId);
var_dump($packageInfo);
```

## Future Enhancements

1. **API Rate Limiting**: Add API call limits for research features
2. **Time-based Limits**: Weekly/daily limits in addition to monthly
3. **Category-specific Limits**: Different limits for different product categories
4. **Advanced Analytics**: Detailed usage analytics and reporting
5. **Auto-upgrade Suggestions**: AI-powered upgrade recommendations
6. **Limit Notifications**: Email notifications when approaching limits

## Security Considerations

- All limit checks are server-side enforced
- Usage tracking is tamper-proof
- Upgrade messages are generated server-side
- No client-side limit bypassing possible

## Performance Optimizations

- Efficient database queries with proper indexing
- Caching of frequently accessed limit data
- Optimized usage calculations
- Minimal overhead on import/research operations

---

This implementation provides a robust, scalable solution for managing package import limits while maintaining excellent user experience and clear upgrade paths. 