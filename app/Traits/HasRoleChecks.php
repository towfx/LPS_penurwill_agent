<?php

namespace App\Traits;

trait HasRoleChecks
{
    /**
     * Check if the user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user has agent role
     */
    public function isAgent(): bool
    {
        return $this->hasRole('agent');
    }

    /**
     * Check if the user has agent_leader role
     */
    public function isAgentLeader(): bool
    {
        return $this->hasRole('agent_leader');
    }

    /**
     * Check if the user has business_partner role
     */
    public function isBusinessPartner(): bool
    {
        return $this->hasRole('business_partner');
    }

    /**
     * Get the appropriate dashboard route based on user role
     */
    public function getDashboardRoute(): string
    {
        if ($this->isAdmin()) {
            return 'admin.dashboard';
        }

        if ($this->isAgent() || $this->isAgentLeader() || $this->isBusinessPartner()) {
            return 'agent.dashboard';
        }

        return 'dashboard';
    }

    /**
     * Get the appropriate dashboard URL based on user role
     */
    public function getDashboardUrl(): string
    {
        if ($this->isAdmin()) {
            return '/admin/dashboard';
        }

        if ($this->isAgent() || $this->isAgentLeader() || $this->isBusinessPartner()) {
            return '/agent/dashboard';
        }

        return '/dashboard';
    }
}
