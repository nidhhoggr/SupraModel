# SupraModel

###Author: Joseph Persie

A DBAL scalable for multiple drivers.

###Configure Settings

You can either provide settings in config/config.yml or config/config.json.
Note: Not all servers support php function yaml_parse_file

You can alterntaively provide crendtials to the server as such:

```php

new AlertModel([
 'dbuser'=>'dbuser',
 'dbname'=>'dbname',
 'dbpassword'=>'dbpassword',
 'dbhost'=>'dbhost',
 'dbdriver'=>'dbdriver',
]);

```

`Or`

```php
$mm = new MetalModel(["dbConfigDirectory" => dirname(__FILE__) . '/../config/']);

```
`And`

```json
{
  "dbuser":"root",
  "dbname":"metalsubgenres",
  "dbpassword":"",
  "dbhost":"localhost",
  "driver":"mysql"
}

```

If a SupraModel child class is instantiated with credentials, they will override whaveter is provided
in config.(json|yml)

###Extends SupraModel

```php

namespace Supra\Alerts\Infrastructure\Persistence\SupraModel\Repository;

use SupraModel\SupraModel;

class PostRepository extends SupraModel {

    public function configure()
    {
        $this->setTable("wp_posts");

        $this->setTableAlias("p");

        $this->setTableIdentifier("ID");
    }

    /**
     * getJobListings
     * 
     *  Using `order` as `ORDER BY, `LIMIT` and ` and `LEFT JOIN`
     *
     *  Other JOIN options besides `LEFT JOIN` include 
     *    `rightjoin`
     *    `innerjoin`
     *
     * @access public
     * @return array $posts
     */
    public function getJobListings()
    {
        $posts = $this->findBy(array(
            'conditions'=> [
                "p.post_type = 'job_listing'",
                "p.post_status = 'publish'",
                "pm.meta_key = 'is_alert_queued'",
                "pm.meta_value = '0'",
            ],
            'leftjoin'=> [
                "wp_postmeta pm" => "p.ID = pm.post_id"
            ],
            'order'=>'ORDER BY ID ASC LIMIT 5'
        ));

        return $posts;
    }

    /**
     * getOutdatedVideos
     * 
     *  using `fields` as SELECT 
     * 
     * @access public
     * @return void
     */
    function getOutdatedVideos() {

        $videos = $this->findBy([
            "leftjoin" =>  [
                "song s" => "v.song_id = s.id",
                "album a" => "a.id = s.album_id",
                "band b" => "b.id = a.band_id"
            ],
            "fields" => ["s.name as songName", "b.name as bandName","a.name as albumName","v.*"],
            "conditions" => ["v.id > 165"]
        ]);

        foreach($videos as $video) {

            $v_id = str_replace("http://gdata.youtube.com/feeds/api/videos/", "", $video->guid);
            $status = $this->getVideoUrlStatus("http://img.youtube.com/vi/{$v_id}/3.jpg");
            if($status === 404) {
                var_dump($video);
                $this->updateOutdatedVideo($video);
            }
        }
    }

    /**
     * getDuplicateVideos
     * 
     * Using a raw query.
     *
     * Must close the object when using fetchNextObject to no interfere with mysqli_result for 
     * the parent query.
     *
     *
     * @access public
     * @return void
     */
    function getDuplicateVideos() {

        $this->query("SELECT link, COUNT(*) c FROM yt_video GROUP BY link HAVING c > 1");

        $duplicateVideos = array();

        $finder = new self;

        while($row = $this->fetchNextObject()) {

            $videos = $finder->findBy([
              "fields" => ["id"],
              "conditions" => ["yt.link LIKE '%{$row->link}%'"],
            ]);

            $duplicateVideos[] = $videos;
        }

        return $duplicateVideos;
    }


}
```

###Leveraging

```php

include dirname(__FILE__) . '/vendor/autoload.php';

use Supra\Alerts\Infrastructure\Persistence\SupraModel\Repository\PostRepository;

$pr = new PostRepository();

//get the last query for debug purposes
$pr->getQuery();

$lastJobListings = $pr->getJobListings();
```

###Persistence

```php

class BirdModel extends SupraModel {
    //SET THE TABLE OF THE MODEL AND THE IDENTIFIER
    public function configure() {
        $this->setTable("bird");
    }
}

$BirdModel = new BirdModel;

//find all by specific conditions and return array
$birds = $BirdModel->findBy(array('conditions'=>array("id=195"),'fetchArray'=>false)));

//change the table
$BirdModel->setTable('bird_taxonomy');

//find one bird
var_dump($BirdModel->findOneBy(array('conditions'=>"name LIKE '%arizona%'")));

//get the sql query
var_dump($BirdModel->getQuery());

//change the table again 
$BirdModel->setTable('bird');

//save a new bird and serialize the colors into the database
$BirdModel->name = 'toojay';

$BirdModel->colors = array('black','white');

//returns the id
$bird_id = $BirdModel->save();

$bird = $BirdModel->findOneBy(array('conditions'=>"name LIKE '%arizona%'"));

$bird->locations = array('arizona','nevada');

$BirdModel->bindObject($BirdModel, $bird);

//its saves all properties and the new bound properties
$bird_id = $BirdModel->save();

```
