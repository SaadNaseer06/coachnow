@extends('layouts.admin')

@section('title', 'Coaches · Admin')
@section('page_title', 'Coaches')
@section('page_subtitle', 'Approve, edit, and assign coaches to park locations')

@section('topbar_actions')
  <button type="button" class="admin-btn admin-btn-primary">+ Add Coach</button>
@endsection

@section('content')
<div class="admin-toolbar">
          <div class="admin-filters">
            <input class="admin-input" type="search" placeholder="Search coaches…">
            <select class="admin-select">
              <option>All statuses</option>
              <option>Active</option>
              <option>Pending</option>
              <option>Paused</option>
            </select>
            <select class="admin-select">
              <option>All locations</option>
              <option>Sommers Bend</option>
              <option>Birdsall</option>
              <option>Los Alamos</option>
            </select>
          </div>
          <span class="text-[12px] text-zinc-500">24 coaches</span>
        </div>

        <div class="admin-card">
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Coach</th>
                  <th>Location</th>
                  <th>Specialty</th>
                  <th>Rate</th>
                  <th>Rating</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><div class="admin-person"><img src="{{ asset("assets/Rectangle 8.png") }}" alt=""><div><strong>Coach Lee</strong><span>lee@coachnow.com</span></div></div></td>
                  <td>Sommers Bend</td>
                  <td>Private Soccer</td>
                  <td>$50</td>
                  <td>4.9</td>
                  <td><span class="admin-badge admin-badge-green">Active</span></td>
                  <td class="whitespace-nowrap">
                    <a href="{{ route('coach-profile') }}" class="admin-btn admin-btn-ghost admin-btn-sm">View</a>
                    <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button>
                  </td>
                </tr>
                <tr>
                  <td><div class="admin-person"><img src="{{ asset("assets/Rectangle 8-2.png") }}" alt=""><div><strong>Coach Heidi</strong><span>heidi@coachnow.com</span></div></div></td>
                  <td>Sommers Bend</td>
                  <td>Small Group</td>
                  <td>$40</td>
                  <td>5.0</td>
                  <td><span class="admin-badge admin-badge-green">Active</span></td>
                  <td class="whitespace-nowrap">
                    <a href="{{ route('coach-profile') }}" class="admin-btn admin-btn-ghost admin-btn-sm">View</a>
                    <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button>
                  </td>
                </tr>
                <tr>
                  <td><div class="admin-person"><img src="{{ asset("assets/Rectangle 8-1.png") }}" alt=""><div><strong>Coach Gabe</strong><span>gabe@coachnow.com</span></div></div></td>
                  <td>Sommers Bend</td>
                  <td>Team Training</td>
                  <td>$45</td>
                  <td>4.8</td>
                  <td><span class="admin-badge admin-badge-green">Active</span></td>
                  <td class="whitespace-nowrap">
                    <a href="{{ route('coach-profile') }}" class="admin-btn admin-btn-ghost admin-btn-sm">View</a>
                    <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button>
                  </td>
                </tr>
                <tr>
                  <td><div class="admin-person"><img src="{{ asset("assets/Rectangle 8.png") }}" alt=""><div><strong>Coach Davos</strong><span>davos@coachnow.com</span></div></div></td>
                  <td>Los Alamos</td>
                  <td>Private Soccer</td>
                  <td>$55</td>
                  <td>4.9</td>
                  <td><span class="admin-badge admin-badge-green">Active</span></td>
                  <td class="whitespace-nowrap">
                    <a href="{{ route('coach-profile') }}" class="admin-btn admin-btn-ghost admin-btn-sm">View</a>
                    <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button>
                  </td>
                </tr>
                <tr>
                  <td><div class="admin-person"><img src="{{ asset("assets/Rectangle 8-1.png") }}" alt=""><div><strong>Coach Ceja</strong><span>ceja@coachnow.com</span></div></div></td>
                  <td>Los Alamos</td>
                  <td>Performance</td>
                  <td>$50</td>
                  <td>4.8</td>
                  <td><span class="admin-badge admin-badge-amber">Pending</span></td>
                  <td class="whitespace-nowrap">
                    <button type="button" class="admin-btn admin-btn-primary admin-btn-sm">Approve</button>
                    <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Decline</button>
                  </td>
                </tr>
                <tr>
                  <td><div class="admin-person"><img src="{{ asset("assets/Rectangle 8-2.png") }}" alt=""><div><strong>Coach Maria</strong><span>maria@coachnow.com</span></div></div></td>
                  <td>Birdsall</td>
                  <td>Small Group</td>
                  <td>$45</td>
                  <td>4.9</td>
                  <td><span class="admin-badge admin-badge-zinc">Paused</span></td>
                  <td class="whitespace-nowrap">
                    <a href="{{ route('coach-profile') }}" class="admin-btn admin-btn-ghost admin-btn-sm">View</a>
                    <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
@endsection
