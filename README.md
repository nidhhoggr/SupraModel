# SupraModel

###Author: Joseph Persie

A DBAL scalable for multiple drivers.

1.

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

If a SupraModel child class is instantiated with credentials, they will override whaveter is provided
in config.(json|yml)

###Extends SupraModel

```php

namespace Supra\Alerts\Infrastructure\Persistence\SupraModel\Repository;

use SupraModel\SupraModel;

class PostRepository extends SupraModel {

    public function configure()
    {
        $this->setTable("wp_posts p");
    }

    public function getJobListings()
    {
        $posts = $this->findBy(array(
            'conditions'=>array(
                "p.post_type = 'job_listing'",
                "p.post_status = 'publish'",
                "pm.meta_key = 'is_alert_queued'",
                "pm.meta_value = '0'",
            ),
            'leftjoin'=>array(
                "wp_postmeta pm" => "p.ID = pm.post_id"
            ),
            'order'=>'ORDER BY ID ASC LIMIT 5',
            'fetchArray'=> true
        ));

        return $posts;
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


