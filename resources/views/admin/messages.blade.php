@extends('layouts.admin')

@section('title', 'Messages · Admin')
@section('page_title', 'Messages')
@section('page_subtitle', 'Contact form submissions from the website')

@section('content')
<div class="admin-toolbar">
  <form method="GET" action="{{ route('admin.messages') }}" class="admin-filters" data-auto-filter>
    <input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search messages…" autocomplete="off">
    <select class="admin-select" name="status">
      <option value="">All messages</option>
      <option value="unread" @selected(($filters['status'] ?? '') === 'unread')>Unread</option>
      <option value="read" @selected(($filters['status'] ?? '') === 'read')>Read</option>
    </select>
  </form>
  <span class="text-[12px] text-zinc-500">{{ $messages->total() }} messages</span>
</div>

<div class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>From</th>
          <th>Topic</th>
          <th>Message</th>
          <th>Source</th>
          <th>Received</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($messages as $message)
          <tr class="{{ $message->isUnread() ? 'font-semibold' : '' }}">
            <td>
              <div>
                <strong>{{ $message->name }}</strong>
                <span class="block text-[12px] font-normal text-zinc-500">{{ $message->email }}</span>
              </div>
            </td>
            <td>{{ $message->topic }}</td>
            <td class="max-w-[320px] whitespace-normal font-normal">{{ $message->message }}</td>
            <td>{{ $message->sourceLabel() }}</td>
            <td>{{ $message->created_at?->format('M j, g:ia') }}</td>
            <td>
              @if ($message->isUnread())
                <form method="POST" action="{{ route('admin.messages.read', $message) }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm">Mark read</button>
                </form>
              @else
                <span class="admin-badge admin-badge-zinc">Read</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-zinc-500 py-8">No contact messages yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.admin.pagination', ['paginator' => $messages])
</div>
@endsection
