<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home');
    }

    public function findACoach()
    {
        return view('pages.find-a-coach');
    }

    public function becomeACoach()
    {
        return view('pages.become-a-coach');
    }

    public function about()
    {
        return view('pages.about');
    }

    public function faq()
    {
        return view('pages.faq');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function login()
    {
        return view('pages.login');
    }

    public function coachProfile()
    {
        return view('pages.coach-profile');
    }

    public function playerDashboard()
    {
        return view('pages.player-dashboard');
    }
}
