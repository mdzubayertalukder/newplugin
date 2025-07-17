<?php

namespace Plugin\Dropshipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchCacheController extends Controller
{
    /**
     * Display the search cache management page
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');
            $status = $request->get('status', '');
            
            $query = DB::connection('mysql')->table('dropshipping_search_cache')
                ->select([
                    'id',
                    'search_query',
                    'search_hash',
                    'total_websites',
                    'is_active',
                    'last_used_at',
                    'usage_count',
                    'created_at',
                    'updated_at'
                ]);
            
            // Apply search filter
            if (!empty($search)) {
                $query->where('search_query', 'LIKE', '%' . $search . '%');
            }
            
            // Apply status filter
            if ($status !== '') {
                $query->where('is_active', (int)$status);
            }
            
            // Order by most recently used
            $query->orderBy('last_used_at', 'desc')
                  ->orderBy('created_at', 'desc');
            
            // Get total count for pagination
            $total = $query->count();
            
            // Apply pagination
            $offset = ($request->get('page', 1) - 1) * $perPage;
            $cacheEntries = $query->offset($offset)->limit($perPage)->get();
            
            // Calculate pagination info
            $currentPage = $request->get('page', 1);
            $totalPages = ceil($total / $perPage);
            
            return view('dropshipping::admin.search-cache.index', compact(
                'cacheEntries',
                'total',
                'currentPage',
                'totalPages',
                'perPage',
                'search',
                'status'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load search cache index', [
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to load search cache data: ' . $e->getMessage());
        }
    }
    
    /**
     * Show details of a specific cache entry
     */
    public function show($id)
    {
        try {
            $cacheEntry = DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('id', $id)
                ->first();
            
            if (!$cacheEntry) {
                return back()->with('error', 'Cache entry not found.');
            }
            
            // Decode JSON data
            $searchResults = json_decode($cacheEntry->search_results, true);
            $searchSummary = json_decode($cacheEntry->search_summary, true);
            
            return view('dropshipping::admin.search-cache.show', compact(
                'cacheEntry',
                'searchResults',
                'searchSummary'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load cache entry details', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to load cache entry: ' . $e->getMessage());
        }
    }
    
    /**
     * Show edit form for a cache entry
     */
    public function edit($id)
    {
        try {
            $cacheEntry = DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('id', $id)
                ->first();
            
            if (!$cacheEntry) {
                return back()->with('error', 'Cache entry not found.');
            }
            
            // Decode JSON data for editing
            $searchResults = json_decode($cacheEntry->search_results, true);
            $searchSummary = json_decode($cacheEntry->search_summary, true);
            
            return view('dropshipping::admin.search-cache.edit', compact(
                'cacheEntry',
                'searchResults',
                'searchSummary'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load cache entry for editing', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to load cache entry: ' . $e->getMessage());
        }
    }
    
    /**
     * Update a cache entry
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'search_query' => 'required|string|max:255',
                'is_active' => 'required|boolean',
                'search_results' => 'required|json',
                'search_summary' => 'nullable|json'
            ]);
            
            // Decode and validate search results
            $searchResults = json_decode($request->search_results, true);
            $searchSummary = $request->search_summary ? json_decode($request->search_summary, true) : null;
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Invalid JSON format in search results or summary.');
            }
            
            // Update the cache entry
            $updated = DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('id', $id)
                ->update([
                    'search_query' => $request->search_query,
                    'search_results' => json_encode($searchResults),
                    'total_websites' => count($searchResults),
                    'search_summary' => $searchSummary ? json_encode($searchSummary) : null,
                    'is_active' => $request->is_active,
                    'updated_at' => now()
                ]);
            
            if ($updated) {
                Log::info('Search cache entry updated', [
                    'id' => $id,
                    'query' => $request->search_query,
                    'is_active' => $request->is_active
                ]);
                
                return redirect()->route('admin.search-cache.show', $id)
                    ->with('success', 'Cache entry updated successfully.');
            } else {
                return back()->with('error', 'Failed to update cache entry.');
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update cache entry', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Failed to update cache entry: ' . $e->getMessage());
        }
    }
    
    /**
     * Toggle active status of a cache entry
     */
    public function toggleStatus($id)
    {
        try {
            $cacheEntry = DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('id', $id)
                ->first();
            
            if (!$cacheEntry) {
                return response()->json(['success' => false, 'message' => 'Cache entry not found.']);
            }
            
            $newStatus = !$cacheEntry->is_active;
            
            DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('id', $id)
                ->update([
                    'is_active' => $newStatus,
                    'updated_at' => now()
                ]);
            
            Log::info('Search cache status toggled', [
                'id' => $id,
                'new_status' => $newStatus
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'new_status' => $newStatus
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to toggle cache status', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete a cache entry
     */
    public function destroy($id)
    {
        try {
            $deleted = DB::connection('mysql')->table('dropshipping_search_cache')
                ->where('id', $id)
                ->delete();
            
            if ($deleted) {
                Log::info('Search cache entry deleted', ['id' => $id]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cache entry deleted successfully.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cache entry not found.'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to delete cache entry', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cache entry: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clear all cache entries
     */
    public function clearAll()
    {
        try {
            $deleted = DB::connection('mysql')->table('dropshipping_search_cache')->delete();
            
            Log::info('All search cache entries cleared', ['count' => $deleted]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully cleared {$deleted} cache entries."
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to clear all cache entries', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get cache statistics
     */
    public function stats()
    {
        try {
            $stats = [
                'total_entries' => DB::connection('mysql')->table('dropshipping_search_cache')->count(),
                'active_entries' => DB::connection('mysql')->table('dropshipping_search_cache')->where('is_active', 1)->count(),
                'inactive_entries' => DB::connection('mysql')->table('dropshipping_search_cache')->where('is_active', 0)->count(),
                'total_usage' => DB::connection('mysql')->table('dropshipping_search_cache')->sum('usage_count'),
                'most_used_query' => DB::connection('mysql')->table('dropshipping_search_cache')
                    ->orderBy('usage_count', 'desc')
                    ->first(['search_query', 'usage_count']),
                'recent_searches' => DB::connection('mysql')->table('dropshipping_search_cache')
                    ->orderBy('last_used_at', 'desc')
                    ->limit(5)
                    ->get(['search_query', 'last_used_at', 'usage_count'])
            ];
            
            return response()->json(['success' => true, 'data' => $stats]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get cache statistics', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ]);
        }
    }
}