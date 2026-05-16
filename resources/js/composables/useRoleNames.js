import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useRoleNames() {
  const page = usePage()

  const roleNames = computed(() => ({
    agent: page.props.systemSettings?.role_name_agent || 'Agent',
    agent_leader: page.props.systemSettings?.role_name_leader || 'Leader',
    business_partner: page.props.systemSettings?.role_name_business_partner || 'Business Partner',
  }))

  const roleNamesPlural = computed(() => ({
    agent: roleNames.value.agent + 's',
    agent_leader: roleNames.value.agent_leader + 's',
    business_partner: roleNames.value.business_partner + 's',
  }))

  const roleLabel = (role) => roleNames.value[role] || role

  return { roleNames, roleNamesPlural, roleLabel }
}
