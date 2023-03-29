@extends("mail.layouts.soundblock")

@section("content")
    <x-p text="We have processed your W-9 Form. You may now request payments from your account. Thank you for using Soundblock!"/>
    <p style="text-align: center;">
        <x-button class="soundblock-email-button" text="Go to Soundblock" :link="$frontendUrl"/>
    </p>
    <p>
        <small><a style="color:dodgerblue;" href="{{ $frontendUrl }}">{{ $frontendUrl }}</a></small>
    </p>
@endsection
