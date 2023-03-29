<?php

namespace App\Listeners\Soundblock;

use App\Events\Soundblock\TrackVolumeNumber as TrackVolumeNumberEvent;

class TrackVolumeNumber
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param TrackVolumeNumberEvent $event
     * @return void
     */
    public function handle(TrackVolumeNumberEvent $event)
    {
        $objTrack = $event->objTrack;
        $intVolumeNumber = $event->newVolumeNumber;
        $objCollection = $objTrack->collections()->latest()->first();

        $orderedFilesGrouped = $objCollection->tracks->groupBy("track_volume_number");

        if ($orderedFilesGrouped->has($intVolumeNumber)) {
            $orderedFilesGrouped[$intVolumeNumber]->push($objTrack);
        } else {
            $orderedFilesGrouped[$intVolumeNumber] = [$objTrack];
        }

        $objTrack->track_volume_number = $intVolumeNumber;
        $objTrack->save();
        $objCollection->refresh();

        $orderedFilesGrouped = $objCollection->tracks->groupBy("track_volume_number");

        foreach ($orderedFilesGrouped as $objTracks) {
            foreach ($objTracks as $key => $objTrack) {
                $objTrack->track_number = $key + 1;
                $objTrack->save();
            }
        }
    }
}
