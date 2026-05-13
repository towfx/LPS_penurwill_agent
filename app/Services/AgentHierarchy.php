<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Collection;

/**
 * Pure helpers for navigating the agent management hierarchy.
 *
 * Roles are ranked: agent (0) < agent_leader (1) < business_partner (2).
 * A child can only have a parent of equal-or-higher rank.
 */
class AgentHierarchy
{
    public const ROLE_RANK = [
        Agent::ROLE_AGENT => 0,
        Agent::ROLE_AGENT_LEADER => 1,
        Agent::ROLE_BUSINESS_PARTNER => 2,
    ];

    /**
     * Direct upline (parent) for an agent.
     */
    public function getDirectManager(Agent $agent): ?Agent
    {
        return $agent->parent_agent_id ? Agent::find($agent->parent_agent_id) : null;
    }

    /**
     * Returns ancestors ordered child → BP top, excluding the agent itself.
     *
     * @return Collection<int, Agent>
     */
    public function getManagementChain(Agent $agent): Collection
    {
        $chain = collect();
        $seen = [];
        $current = $this->getDirectManager($agent);
        while ($current && ! isset($seen[$current->id])) {
            $seen[$current->id] = true;
            $chain->push($current);
            $current = $this->getDirectManager($current);
        }
        return $chain;
    }

    /**
     * Direct subordinates, optionally filtered by role.
     *
     * @return Collection<int, Agent>
     */
    public function getSubordinates(Agent $agent, ?string $role = null): Collection
    {
        $query = $agent->subordinates();
        if ($role) {
            $query->where('agent_role', $role);
        }
        return $query->get();
    }

    /**
     * Recursive descendants of an agent (all the way down).
     *
     * @return Collection<int, Agent>
     */
    public function getAllDescendants(Agent $agent): Collection
    {
        return $agent->descendants();
    }

    /**
     * Validate a proposed (child, parent) hierarchy change.
     *
     * @return array<int, string> error messages (empty if valid)
     */
    public function validateHierarchyChange(Agent $child, ?Agent $newParent): array
    {
        $errors = [];

        if ($newParent && $newParent->id === $child->id) {
            $errors[] = 'An agent cannot be its own parent.';
            return $errors;
        }

        if ($newParent === null) {
            // Root agent (ID 1) is allowed to have no parent.
            if ($child->id !== 1 && ($child->agent_role ?? Agent::ROLE_AGENT) !== Agent::ROLE_BUSINESS_PARTNER) {
                $errors[] = 'This agent role requires a parent.';
            }
            return $errors;
        }

        $childRole = $child->agent_role ?? Agent::ROLE_AGENT;
        $parentRole = $newParent->agent_role ?? Agent::ROLE_AGENT;

        if ($childRole === Agent::ROLE_BUSINESS_PARTNER) {
            if ($newParent->id !== 1) {
                $errors[] = 'Business Partners must be under Agent ID 1.';
            }
        } elseif ($childRole === Agent::ROLE_AGENT_LEADER) {
            if ($parentRole !== Agent::ROLE_BUSINESS_PARTNER) {
                $errors[] = 'Agent Leaders must be under a Business Partner.';
            }
        } elseif ($childRole === Agent::ROLE_AGENT) {
            if ($parentRole !== Agent::ROLE_AGENT_LEADER) {
                $errors[] = 'Agents must be under an Agent Leader.';
            }
        }

        if ($this->wouldCreateCycle($child, $newParent)) {
            $errors[] = 'This change would create a cycle in the hierarchy.';
        }

        return $errors;
    }

    /**
     * Check whether assigning $newParent to $child would create a cycle.
     */
    public function wouldCreateCycle(Agent $child, Agent $newParent): bool
    {
        $current = $newParent;
        $seen = [];
        while ($current) {
            if ($current->id === $child->id) {
                return true;
            }
            if (isset($seen[$current->id])) {
                return true; // existing cycle
            }
            $seen[$current->id] = true;
            $current = $current->parent_agent_id ? Agent::find($current->parent_agent_id) : null;
        }
        return false;
    }
}
