# Google Custom Search API Setup Guide

## Overview
The Google Custom Search API integration allows your dropshipping application to fetch real market data from Bangladesh e-commerce sites like Daraz, Pickaboo, AjkerDeal, etc.

## Step 1: Enable Google Custom Search API

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Navigate to "APIs & Services" > "Library"
4. Search for "Custom Search API"
5. Click on "Custom Search API" and click "Enable"

## Step 2: Create API Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "API Key"
3. Copy the generated API key
4. (Optional) Restrict the API key to only Custom Search API for security

## Step 3: Create Custom Search Engine

1. Go to [Google Custom Search Engine](https://cse.google.com/cse/)
2. Click "Add" to create a new search engine
3. In "Sites to search", add these Bangladesh e-commerce sites:
   ```
   daraz.com.bd
   pickaboo.com
   ajkerdeal.com
   othoba.com
   rokomari.com
   bagdoom.com
   chaldal.com
   ```
4. Give your search engine a name (e.g., "Bangladesh E-commerce Search")
5. Click "Create"
6. After creation, click on your search engine
7. Go to "Setup" tab
8. Copy the "Search engine ID" (it looks like: `017576662512468239146:omuauf_lfve`)

## Step 4: Configure Search Engine Settings

1. In your Custom Search Engine settings, go to "Setup" tab
2. Under "Basics", make sure "Search the entire web" is enabled
3. Under "Advanced", set:
   - SafeSearch: Moderate
   - Country: Bangladesh
   - Language: English

## Step 5: Configure in Your Application

1. Go to your admin panel > AI Settings
2. Enter the API Key from Step 2
3. Enter the Search Engine ID from Step 3
4. Click "Test Google Search API" to verify the setup

## Troubleshooting

### Error: "Network response was not ok"
- Check if both API Key and Search Engine ID are entered correctly
- Verify that the Custom Search API is enabled in Google Cloud Console
- Make sure your API key has permissions for Custom Search API

### Error: "API key not valid"
- Regenerate your API key in Google Cloud Console
- Check if there are any restrictions on your API key

### Error: "Invalid search engine ID"
- Double-check the Search Engine ID from your Custom Search Engine
- Make sure the search engine is active and properly configured

### No search results
- Verify that your Custom Search Engine includes the Bangladesh e-commerce sites
- Check if "Search the entire web" is enabled
- Try testing with a simple query like "smartphone"

## API Limits

- Google Custom Search API has a free tier of 100 queries per day
- For higher usage, you'll need to enable billing in Google Cloud Console
- Each search costs approximately $5 per 1000 queries after the free tier

## Testing

Use the test script provided (`test_google_search.php`) to verify your setup:

```bash
php test_google_search.php
```

Make sure to add your Search Engine ID to the script before running.

## Support

If you encounter issues:
1. Check the Laravel logs for detailed error messages
2. Verify your Google Cloud Console settings
3. Test the API directly using the test script
4. Ensure your Custom Search Engine is properly configured with Bangladesh sites