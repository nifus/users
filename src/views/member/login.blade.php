@extends('layouts.login')

@section('content')
<div style="margin: auto 250px">
    <div class="login_head">
        <h1>Вход</h1>
    </div>
    @if( $login_form->isSubmit()==true && $login_form->fails()!==false )
        <div class="alert alert-danger" role="alert">{{ $login_form->error() }}</div>
    @endif
    {{ $login_form->render() }}

</div>
<br style="clear: both"/>
@stop