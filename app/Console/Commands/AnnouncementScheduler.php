<?php

namespace App\Console\Commands;

use Statamic\Facades\Entry;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

class AnnouncementScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wcas:announcement-scheduler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Announcements publication status based on dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $announcements = Entry::query()
            ->where('collection', 'announcements')
            ->get()
            ->each(function( $item, $key ) {
                $today = (new Carbon())->createMidnightDate();
                $publishOn = new Carbon($item->get('publish_on'));
                $unpublishOn = new Carbon($item->get('unpublish_on'));

                if( $item->get('skip_automation') ) return;
                dump('Checking');

                if($publishOn <= $today && $unpublishOn >= $today && !$item->published()) {
                    $this->publishAnnouncement($item);
                    return;
                }

                if(($publishOn >= $today || $unpublishOn <= $today) && $item->published()) {
                    $this->unpublishAnnouncement($item);
                    return;
                }

            });

    }

    protected function publishAnnouncement($item)
    {
        dump('Publish Announcement');
        $item->publish();
    }

    protected function unpublishAnnouncement($item)
    {
        dump('Unpublish Announcement');
        $item->unpublish();
    }
}
