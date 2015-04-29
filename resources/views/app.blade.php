@extends('master')

@section('js')
	<script src="{{ asset('js/moment.js') }}"></script>
	<script src="{{ asset('js/moment.tr.js') }}"></script>
	<script src="{{ asset('js/jquery.timeago.js') }}"></script>
	<script src="{{ asset('js/jquery.timeago.tr.js') }}"></script>
	<script src="{{ asset('js/chat.js') }}"></script>
@stop

@section('content')
<div class="container">
	<div class="row">
		<div class="col-xs-8 col-md-9">
			<div class="panel panel-default">
				<div class="panel-heading">
					<span id="chat-name">Chat</span>
				</div>
				<div class="panel-body">
					<div id="messages">
					</div><!-- .messages -->
					<input type="text" class="form-control" id="message">
				</div>
			</div><!-- .panel -->
		</div><!-- .col-9 -->
		<div class="col-xs-4 col-md-3">
			<div id="active-users">
				<ul class="list-group groups">
					<li class="list-group-item" data-group-id="1" id="group-1"><span>Genel</span></li>
				</ul>
				<ul class="list-group users"></ul>
			</div><!-- .active-userss -->
		</div><!-- .col-3 -->
	</div><!-- .row -->
</div><!-- .container -->

<input type="hidden" id="user_id" value="{{ Auth::id() }}">
@endsection
