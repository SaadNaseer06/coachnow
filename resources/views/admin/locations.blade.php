@extends('layouts.admin')

@section('title', 'Locations · Admin')
@section('page_title', 'Locations')
@section('page_subtitle', 'Park locations shown on the homepage and search')

@section('topbar_actions')
  <button type="button" class="admin-btn admin-btn-primary">+ Add Location</button>
@endsection

@section('content')
<div class="admin-kpi-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
          <article class="admin-kpi">
            <div class="admin-kpi-label">Total Parks</div>
            <div class="admin-kpi-value">6</div>
            <div class="admin-kpi-trend flat">Murrieta &amp; Temecula</div>
          </article>
          <article class="admin-kpi">
            <div class="admin-kpi-label">Assigned Coaches</div>
            <div class="admin-kpi-value">14</div>
            <div class="admin-kpi-trend up">↑ 2 this week</div>
          </article>
          <article class="admin-kpi">
            <div class="admin-kpi-label">Avg Distance</div>
            <div class="admin-kpi-value">3.8</div>
            <div class="admin-kpi-trend flat">miles from users</div>
          </article>
        </div>

        <div class="admin-card">
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Park</th>
                  <th>Area</th>
                  <th>Distance</th>
                  <th>Coaches</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>Sommers Bend</strong></td>
                  <td>Murrieta, CA</td>
                  <td>1.2 mi</td>
                  <td>Lee · Heidi · Gabe</td>
                  <td><span class="admin-badge admin-badge-green">Live</span></td>
                  <td><button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button></td>
                </tr>
                <tr>
                  <td><strong>Birdsall</strong></td>
                  <td>Temecula, CA</td>
                  <td>2.4 mi</td>
                  <td>Alex · Maria</td>
                  <td><span class="admin-badge admin-badge-green">Live</span></td>
                  <td><button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button></td>
                </tr>
                <tr>
                  <td><strong>Los Alamos</strong></td>
                  <td>Murrieta, CA</td>
                  <td>3.1 mi</td>
                  <td>Davos · Ceja</td>
                  <td><span class="admin-badge admin-badge-green">Live</span></td>
                  <td><button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button></td>
                </tr>
                <tr>
                  <td><strong>Alta Murrieta</strong></td>
                  <td>Murrieta, CA</td>
                  <td>4.0 mi</td>
                  <td>Mike · Jordan</td>
                  <td><span class="admin-badge admin-badge-green">Live</span></td>
                  <td><button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button></td>
                </tr>
                <tr>
                  <td><strong>Temecula Sports Park</strong></td>
                  <td>Temecula, CA</td>
                  <td>5.2 mi</td>
                  <td>Sam · Riley · Pat</td>
                  <td><span class="admin-badge admin-badge-green">Live</span></td>
                  <td><button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button></td>
                </tr>
                <tr>
                  <td><strong>California Oaks</strong></td>
                  <td>Murrieta, CA</td>
                  <td>6.8 mi</td>
                  <td>Nina · Omar</td>
                  <td><span class="admin-badge admin-badge-amber">Draft</span></td>
                  <td><button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Edit</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
@endsection
