@extends('layouts.admin')

@section('title', 'Athletes · Admin')
@section('page_title', 'Athletes')
@section('page_subtitle', 'Families and players booking training sessions')

@section('content')
<div class="admin-toolbar">
          <div class="admin-filters">
            <input class="admin-input" type="search" placeholder="Search athletes…">
            <select class="admin-select">
              <option>All activity</option>
              <option>Active this month</option>
              <option>New</option>
            </select>
          </div>
        </div>

        <div class="admin-card">
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Athlete / Family</th>
                  <th>Email</th>
                  <th>Sessions</th>
                  <th>Last Booking</th>
                  <th>Preferred Park</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><div class="admin-person"><div class="admin-person-fallback">JM</div><div><strong>Jordan M.</strong><span>Ages 10–12</span></div></div></td>
                  <td>jordan@email.com</td>
                  <td>12</td>
                  <td>Today</td>
                  <td>Sommers Bend</td>
                  <td><span class="admin-badge admin-badge-green">Active</span></td>
                </tr>
                <tr>
                  <td><div class="admin-person"><div class="admin-person-fallback">AS</div><div><strong>Ava S.</strong><span>Ages 8–10</span></div></div></td>
                  <td>ava@email.com</td>
                  <td>7</td>
                  <td>Today</td>
                  <td>Sommers Bend</td>
                  <td><span class="admin-badge admin-badge-green">Active</span></td>
                </tr>
                <tr>
                  <td><div class="admin-person"><div class="admin-person-fallback">DR</div><div><strong>Diego R.</strong><span>Ages 12–14</span></div></div></td>
                  <td>diego@email.com</td>
                  <td>5</td>
                  <td>Tomorrow</td>
                  <td>Los Alamos</td>
                  <td><span class="admin-badge admin-badge-green">Active</span></td>
                </tr>
                <tr>
                  <td><div class="admin-person"><div class="admin-person-fallback">NK</div><div><strong>Noah K.</strong><span>Team player</span></div></div></td>
                  <td>noah@email.com</td>
                  <td>3</td>
                  <td>Sat</td>
                  <td>Birdsall</td>
                  <td><span class="admin-badge admin-badge-zinc">New</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
@endsection
