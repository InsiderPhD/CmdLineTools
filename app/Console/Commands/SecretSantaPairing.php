<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class SecretSantaPairing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmd:santa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a secret santa match up from a json file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // people.json format
        /*  {
            "timestamp": "2019/11/19 7:57:56 pm GMT",
            "name": "John Smith",
            "location": "EU",
            "address": "123 main st",
            "ship": "UK;EU"
        },*/

        // load people.json into a collection
        $storageDisk = 'local';
        $file = 'santa/people.json';

        $contents = null;
        try {
            $contents = Storage::disk($storageDisk)->get($file);
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
        }

        $this->info('Loading config from ' . Storage::disk($storageDisk)->path($file));

        $listOfParticipants =  json_decode($contents, true);

        $finalList = [];

        foreach ($listOfParticipants as $participant)
        {
            //  what people could this person match with
            $matches = [];

            foreach ($listOfParticipants as $match)
            {
                // if this is not the same person
                if($match['name']!=$participant['name'])
                {
                    if ( strstr( $participant['ship'], $match['location']) ) {
                        array_push($matches, $match['name']);
                    }

                }
            }

            shuffle($matches);
            $participant['matches'] = $matches;



            array_push($finalList,$participant);
        }

        shuffle($finalList);

        $successfulList = false;
        $iterations = 0;

        //$finalMatches = [];


        while(!$successfulList)
        {
            $iterations++;
            $matched = "";
            shuffle($finalList);
            $finalMatches = [];

            $this->info("Match attempt: " .$iterations);

            //dd($finalList);
            $listVars = "";
            foreach ($finalList as $item) $listVars = $listVars. ", " . $item['name'];
            $this->info("list: " . $listVars);

            foreach ($finalList as $item)
            {
                // attempt to match this person

                // has this person been matched?
                $hasBeenMatched = false;

                // go through each potential match
                shuffle($item["matches"]);


                for($i = 0; $i < sizeof($item["matches"]); $i++)
                {
                    //has this person been matched?
                    if(!$hasBeenMatched) {
                        // has this potential match been matched?
                        if (!strstr($matched, $item["matches"][$i])) {
                            // no, push it to the final matches array

                            $matchArray = [];
                            $personArray = [];

                            foreach ($listOfParticipants as $value)
                            {
                                if($value['name'] ==  $item["matches"][$i]) $matchArray = $value;
                                if($value['name'] ==  $item["name"]) $personArray = $value;
                            }

                            //array_push($finalMatches, ["person" => $item['name'], "match" => $item["matches"][$i]]);
                            array_push($finalMatches, ["person" => $personArray, "match" => $matchArray]);

                            // add it to the list of matches
                            $matched = $matched . " " . $item["matches"][$i];

                            $this->info("    " .$item['name'] . " to ship to " . $item["matches"][$i]);

                            $hasBeenMatched = true;

                        } // if it already was matched + we've reached the end of the list
                        elseif ($i - 1 == $i < sizeof($item["matches"])) {
                            // matching was unsuccessful go to the next
                            $successfulList = false;
                            break;

                        }
                    }
                }
            }
            // if final matches is the same size as the original list we have matched everyone successfully
            if(sizeof($finalMatches)==sizeof($finalList)){
                $successfulList = true;
            }

            $finalMatch = null;
        }


        foreach ($finalMatches as $match)
        {
            $this->info($match["person"]["name"] . " will ship to " . $match["match"]["name"]);
            $this->info("their address is: " . $match["match"]["address"]);
            $this->info("and they are located in the " . $match["match"]["location"]);
            $this->info("");
        }

    }
}
