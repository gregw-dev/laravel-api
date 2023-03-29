@extends("mail.layouts.soundblock")

@section("content")
    <x-p text="The deployment status of one or more platforms you requested we submit your project to has changed. Please see the updated status below."/>

    @foreach ($project_deployments as $project_title => $projectDeployment)
        <x-head-line class="soundblock-email-headline" title="{{ $project_title }}"/>

        @foreach ($projectDeployment as $deployment)
            <span class="font-book">
                <b>{{ $deployment["platform"] }} - </b> {{ $deployment["status"] }}<br>
            </span>
        @endforeach
    @endforeach

    <x-p text="If you have any questions about this deployment, please reach out to us from your support desk at Soundblock. Thank you!"/>

    <p style="text-align: center;">
        <x-button class="soundblock-email-button" text="Go to Soundblock" :link="$link"/>
    </p>
    <p>
        <small><a style="color:dodgerblue;" href="{{ $link }}">{{ $link }}</a></small>
    </p>
@endsection
