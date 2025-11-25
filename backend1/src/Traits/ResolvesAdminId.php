<?php

namespace App\Traits;

use App\Models\Admin;

/**
 * Trait for resolving admin IDs to root admin for data scoping
 * 
 * This ensures principals and sub-admins see their parent admin's data
 */
trait ResolvesAdminId
{
    /**
     * Resolve admin ID to root admin for data scoping
     * 
     * @param mixed $user The user object from JWT
     * @return int The root admin ID
     */
    protected function resolveAdminId($requestOrUser, $maybeUser = null): int
    {
        // Support older calls that passed only the user
        $request = $maybeUser ? $requestOrUser : null;
        $user = $maybeUser ?: $requestOrUser;

        $adminId = null;
        $role = '';

        if ($request) {
            $adminId = $request->getAttribute('admin_id') ?? $request->getAttribute('account_id');
        }

        if (is_object($user)) {
            $adminId = $adminId ?? ($user->admin_id ?? $user->account_id ?? $user->id ?? null);
            $role = strtolower($user->role ?? '');
        }

        // Default to 0 if nothing found to avoid type errors
        $adminId = (int)($adminId ?? 0);

        // For non-admin roles, use admin_id directly
        if (!in_array($role, ['admin', 'principal', 'super_admin'], true)) {
            return $adminId;
        }
        
        // For admins and principals, resolve to root admin
        try {
            $adminModel = new Admin();
            return $adminModel->getRootAdminId($adminId);
        } catch (\Exception $e) {
            // Fallback to original ID if resolution fails
            error_log('Failed to resolve admin ID: ' . $e->getMessage());
            return $adminId;
        }
    }
    
    /**
     * Get the admin ID from request or user
     * This is the main method to use in controllers
     * 
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param mixed $user
     * @return int
     */
    protected function getAdminId($request, $user): int
    {
        return $this->resolveAdminId($request, $user);
    }
}
