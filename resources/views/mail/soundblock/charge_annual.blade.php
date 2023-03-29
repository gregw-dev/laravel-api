@extends("mail.layouts.soundblock")

@section("content")
    <x-head-line class="soundblock-email-headline" title="{{ $subject }}"/>

    @if ($status)
        <x-p text="We successfully processed the annual charge for your account."/>
    @else
        <x-p text="We would like to inform you that the annual charge for your account was declined. Please update your payment method and try again or contact customer service if you need assistance."/>
    @endif

    @foreach ($account as $accountKey => $accountValue)
        <span class="font-book">
            <b>{{ $accountKey }} - </b> {{ $accountValue }}<br>
        </span>
    @endforeach

    <p style="text-align: center;">
        <x-button class="soundblock-email-button" text="Soundblock Accounts" :link="$account_link"/>
        <x-button class="soundblock-email-button" text="Soundblock Support" :link="$support_link"/>
    </p>
    <p>
        <small><a style="color:dodgerblue;" href="{{ $account_link }}">{{ $account_link }}</a></small>
        <small><a style="color:dodgerblue;" href="{{ $support_link }}">{{ $support_link }}</a></small>
    </p>
@endsection
