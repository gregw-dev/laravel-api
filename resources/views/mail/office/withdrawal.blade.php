@extends("mail.layouts.soundblock")

@section("content")
    <x-head-line class="soundblock-email-headline" title="Withdrawal Request"/>
    <x-p text="{{ $userName }} requested a withdrawal of ${{ number_format($withdrawalAmount, 2) }}."/>

    <p style="text-align: center;">
        <x-button class="soundblock-email-button" text="Office Payments Page" :link="$frontendUrl"/>
    </p>
    <p>
        <small><a style="color:dodgerblue;" href="{{ $frontendUrl }}">{{ $frontendUrl }}</a></small>
    </p>
@endsection
