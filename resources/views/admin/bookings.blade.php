@extends('layouts.admin')

@section('title', 'Bookings · Admin')
@section('page_title', 'Bookings')
@section('page_subtitle', 'Track sessions by date, coach, and park')

@section('content')
<div class="admin-toolbar">
          <div class="admin-filters">
            <input class="admin-input" type="search" placeholder="Search bookings…">
            <select class="admin-select">
              <option>All statuses</option>
              <option>Confirmed</option>
              <option>Pending</option>
              <option>Cancelled</option>
            </select>
            <select class="admin-select">
              <option>All dates</option>
              <option>Today</option>
              <option>This week</option>
              <option>This month</option>
            </select>
          </div>
        </div>

        <div class="admin-card">
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Athlete</th>
                  <th>Coach</th>
                  <th>Location</th>
                  <th>Session</th>
                  <th>When</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>#CN-1042</td>
                  <td>Jordan M.</td>
                  <td>Coach Lee</td>
                  <td>Sommers Bend</td>
                  <td>Private</td>
                  <td>Today · 4:00 PM</td>
                  <td>$50</td>
                  <td><span class="admin-badge admin-badge-green">Confirmed</span></td>
                </tr>
                <tr>
                  <td>#CN-1041</td>
                  <td>Ava S.</td>
                  <td>Coach Heidi</td>
                  <td>Sommers Bend</td>
                  <td>Small Group</td>
                  <td>Today · 5:30 PM</td>
                  <td>$40</td>
                  <td><span class="admin-badge admin-badge-amber">Pending</span></td>
                </tr>
                <tr>
                  <td>#CN-1040</td>
                  <td>Diego R.</td>
                  <td>Coach Davos</td>
                  <td>Los Alamos</td>
                  <td>Private</td>
                  <td>Tomorrow · 6:15 PM</td>
                  <td>$55</td>
                  <td><span class="admin-badge admin-badge-green">Confirmed</span></td>
                </tr>
                <tr>
                  <td>#CN-1039</td>
                  <td>Noah K.</td>
                  <td>Coach Gabe</td>
                  <td>Birdsall</td>
                  <td>Team</td>
                  <td>Sat · 9:00 AM</td>
                  <td>$45</td>
                  <td><span class="admin-badge admin-badge-green">Confirmed</span></td>
                </tr>
                <tr>
                  <td>#CN-1038</td>
                  <td>Mia L.</td>
                  <td>Coach Ceja</td>
                  <td>Los Alamos</td>
                  <td>Performance</td>
                  <td>Sun · 10:00 AM</td>
                  <td>$50</td>
                  <td><span class="admin-badge admin-badge-red">Cancelled</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
@endsection
