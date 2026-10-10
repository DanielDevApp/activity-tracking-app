@props(['role'])
<span class="badge badge--{{ $role === 'admin' ? 'admin' : 'member' }}"><x-icon :name="$role === 'admin' ? 'shield' : 'user'" :size="12" />{{ ucfirst($role) }}</span>
