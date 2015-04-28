@extends('app')

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
		<div class="col-xs-9">
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
		<div class="col-xs-3">
			<div id="active-users">
				<ul class="list-group">
					<li class="list-group-item">Item 1</li>
					<li class="list-group-item">Item 2</li>
					<li class="list-group-item">Item 3</li>
				</ul>
			</div>
		</div><!-- .col-3 -->
	</div><!-- .row -->
</div><!-- .container -->

<input type="hidden" id="user_id" value="{{ Auth::id() }}">
@endsection
