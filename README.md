Contao ChurchDesk Bundle
=======================

[![](https://img.shields.io/packagist/v/numero2/contao-churchdesk.svg?style=flat-square)](https://packagist.org/packages/numero2/contao-churchdesk) [![License: LGPL v3](https://img.shields.io/badge/License-LGPL%20v3-blue.svg?style=flat-square)](http://www.gnu.org/licenses/lgpl-3.0)

About
--

Import news and events from [ChurchDesk](https://www.churchdesk.com/) as news into Contao.

System requirements
--

* [Contao 4.13](https://github.com/contao/contao) (or newer)

Installation
--

* Install via Contao Manager or Composer (`composer require numero2/contao-churchdesk`)
* Run a database update via the Contao-Installtool or using the [contao:migrate](https://docs.contao.org/dev/reference/commands/) command.

Configuration
--
* Enter credentials in the `config.yaml`
  ``` yaml
  church_desk:
      api:
          organization_id: 123
          partner_token: 'abc'
  ```
* Configure the event calendar and or news archive 


Hooks
--

By default the bundle only imports certain information from ChurchDesk. If you need more data you can import them on your own using the `parseChurchDeskEntry` hook:

```php
// src/EventListener/ParseChurchDeskEntryListener.php
namespace App\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Model;
use Contao\NewsModel;
use Contao\CalendarEventsModel;

/**
 * @Hook("parseChurchDeskEntry")
 */
class ParseChurchDeskEntryListener {
    
    public function __invoke( Model $model, array $apiData, bool $isUpdate ): void {

        if( $model instanceof CalendarEventsModel ) {
            $model->something = $apiData->something;
        }

        if( $model instanceof NewsModel ) {
            $model->anything = $apiData->anything;
        }
    }
}
```

Console Commands
--

An automatic import of all events and blog entries can be started via the command `vendor/bin/contao-console contao:churchdesk:import`.