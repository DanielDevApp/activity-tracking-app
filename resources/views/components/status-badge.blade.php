@props(['status'])
<span class="badge badge--{{ $status }}"><x-icon :name="$status === 'done' ? 'check' : 'clock'" :size="12" />{{ $status === 'done' ? 'Done' : 'Pending' }}</span>
