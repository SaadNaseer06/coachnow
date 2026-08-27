@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview of coaches, bookings, and park activity')

@section('topbar_actions')
  <label class="admin-search">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="search" placeholder="Search coaches, bookings…">
          </label>
          <a href="{{ route('admin.coaches') }}" class="admin-btn admin-btn-primary">+ Add Coach</a>
@endsection

@section('content')
<section class="admin-kpi-grid">
          <article class="admin-kpi">
            <div class="admin-kpi-label">Active Coaches</div>
            <div class="admin-kpi-value">24</div>
            <div class="admin-kpi-trend up">↑ 3 this month</div>
          </article>
          <article class="admin-kpi">
            <div class="admin-kpi-label">Bookings Today</div>
            <div class="admin-kpi-value">18</div>
            <div class="admin-kpi-trend up">↑ 12% vs yesterday</div>
          </article>
          <article class="admin-kpi">
            <div class="admin-kpi-label">Park Locations</div>
            <div class="admin-kpi-value">6</div>
            <div class="admin-kpi-trend flat">Sommers Bend · Birdsall · more</div>
          </article>
          <article class="admin-kpi">
            <div class="admin-kpi-label">Revenue (MTD)</div>
            <div class="admin-kpi-value">$4.2k</div>
            <div class="admin-kpi-trend up">↑ 8% vs last month</div>
          </article>
        </section>

        <section class="admin-grid-2">
          <div class="admin-card">
            <div class="admin-card-header">
              <div>
                <h2>Recent Bookings</h2>
                <p>Latest sessions across Murrieta &amp; Temecula</p>
              </div>
              <a href="{{ route('admin.bookings') }}" class="admin-btn admin-btn-ghost admin-btn-sm">View all</a>
            </div>
            <div class="admin-table-wrap">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Athlete</th>
                    <th>Coach</th>
                    <th>Location</th>
                    <th>When</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><div class="admin-person"><div class="admin-person-fallback">JM</div><div><strong>Jordan M.</strong><span>Private · $50</span></div></div></td>
                    <td>Coach Lee</td>
                    <td>Sommers Bend</td>
                    <td>Today · 4:00 PM</td>
                    <td><span class="admin-badge admin-badge-green">Confirmed</span></td>
                  </tr>
                  <tr>
                    <td><div class="admin-person"><div class="admin-person-fallback">AS</div><div><strong>Ava S.</strong><span>Small Group · $40</span></div></div></td>
                    <td>Coach Heidi</td>
                    <td>Sommers Bend</td>
                    <td>Today · 5:30 PM</td>
                    <td><span class="admin-badge admin-badge-amber">Pending</span></td>
                  </tr>
                  <tr>
                    <td><div class="admin-person"><div class="admin-person-fallback">DR</div><div><strong>Diego R.</strong><span>Private · $55</span></div></div></td>
                    <td>Coach Davos</td>
                    <td>Los Alamos</td>
                    <td>Tomorrow · 6:15 PM</td>
                    <td><span class="admin-badge admin-badge-green">Confirmed</span></td>
                  </tr>
                  <tr>
                    <td><div class="admin-person"><div class="admin-person-fallback">NK</div><div><strong>Noah K.</strong><span>Team · $45</span></div></div></td>
                    <td>Coach Gabe</td>
                    <td>Birdsall</td>
                    <td>Sat · 9:00 AM</td>
                    <td><span class="admin-badge admin-badge-green">Confirmed</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="admin-card">
            <div class="admin-card-header">
              <div>
                <h2>Top Locations</h2>
                <p>Parks with the most coach activity</p>
              </div>
              <a href="{{ route('admin.locations') }}" class="admin-btn admin-btn-ghost admin-btn-sm">Manage</a>
            </div>
            <div class="admin-card-body">
              <div class="admin-list">
                <div class="admin-list-item">
                  <div><strong>Sommers Bend</strong><span>3 coaches · 1.2 mi</span></div>
                  <span class="admin-badge admin-badge-green">Busy</span>
                </div>
                <div class="admin-list-item">
                  <div><strong>Los Alamos</strong><span>2 coaches · 3.1 mi</span></div>
                  <span class="admin-badge admin-badge-zinc">Steady</span>
                </div>
                <div class="admin-list-item">
                  <div><strong>Birdsall</strong><span>2 coaches · 2.4 mi</span></div>
                  <span class="admin-badge admin-badge-zinc">Steady</span>
                </div>
                <div class="admin-list-item">
                  <div><strong>Temecula Sports Park</strong><span>3 coaches · 5.2 mi</span></div>
                  <span class="admin-badge admin-badge-amber">Growing</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="admin-card">
          <div class="admin-card-header">
            <div>
              <h2>Coach Approvals</h2>
              <p>New applications waiting for review</p>
            </div>
            <a href="{{ route('admin.coaches') }}" class="admin-btn admin-btn-ghost admin-btn-sm">Open queue</a>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Coach</th>
                  <th>Specialty</th>
                  <th>Preferred Park</th>
                  <th>Submitted</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><div class="admin-person"><img src="{{ asset("assets/Rectangle 8.png") }}" alt=""><div><strong>Coach Riley</strong><span>riley@email.com</span></div></div></td>
                  <td>Private Soccer</td>
                  <td>Alta Murrieta</td>
                  <td>Aug 22</td>
                  <td class="whitespace-nowrap">
                    <button type="button" class="admin-btn admin-btn-primary admin-btn-sm">Approve</button>
                    <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Decline</button>
                  </td>
                </tr>
                <tr>
                  <td><div class="admin-person"><img src="{{ asset("assets/Rectangle 8-2.png") }}" alt=""><div><strong>Coach Nina</strong><span>nina@email.com</span></div></div></td>
                  <td>Youth Development</td>
                  <td>California Oaks</td>
                  <td>Aug 21</td>
                  <td class="whitespace-nowrap">
                    <button type="button" class="admin-btn admin-btn-primary admin-btn-sm">Approve</button>
                    <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Decline</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
@endsection
