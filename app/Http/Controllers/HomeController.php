<?php namespace Chat\Http\Controllers;

class HomeController extends Controller {

	public function __construct()
	{
		$this->middleware('auth');
	}

	/*
	|--------------------------------------------------------------------------
	| Anasayfa
	|--------------------------------------------------------------------------
	*/
	public function index()
	{
		return view('home');
	}

}
