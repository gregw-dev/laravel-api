@extends("mail.layouts.soundblock")

@section("content")
    <x-head-line class="soundblock-email-headline" title="{{ $plan_type }} Renewal Notice"/>

    <x-p text="Hello, {{ $user_name }}!"/>
    <x-p text="We would like to inform you of an upcoming annual charge to your account."/>

    @foreach ($account as $accountKey => $accountValue)
        <span class="font-book">
            <b>{{ $accountKey }} - </b> {{ $accountValue }}<br>
        </span>
    @endforeach

    <x-p text='If you wish to cancel your service before that date, you may do so from the "Accounts" page on Soundblock. If you have any questions about this charge or need assistance with anything else, please reach out to us using our support desk.'/>
    <x-p text="Thank you for using Soundblock!"/>

    <p style="text-align: center;">
        <x-button class="soundblock-email-button" text="Soundblock Accounts" :link="$link"/>
    </p>
    <p>
        <small><a style="color:dodgerblue;" href="{{ $link }}">{{ $link }}</a></small>
    </p>
@endsection
