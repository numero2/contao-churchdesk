Contao ChurchDesk Bundle
=======================

[![](https://img.shields.io/packagist/v/numero2/contao-churchdesk.svg?style=flat-square)](https://packagist.org/packages/numero2/contao-churchdesk) [![License: LGPL v3](https://img.shields.io/badge/License-LGPL%20v3-blue.svg?style=flat-square)](http://www.gnu.org/licenses/lgpl-3.0)

About
--

Import news and events from [ChurchDesk](https://www.churchdesk.com/) as news into Contao.

System requirements
--

* [Contao 5.3](https://github.com/contao/contao) (or newer)

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


Events
--

By default the bundle only imports certain information from ChurchDesk. If you need more data you can import them on your own using the `contao.churchdesk_import_entry` event:

```php
// src/EventListener/ChurchDeskImportEntryListener.php
namespace App\EventListener;

use numero2\ChurchDeskBundle\Event\ChurchDeskEvents;
use numero2\ChurchDeskBundle\Event\ImportEntryEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(ChurchDeskEvents::IMPORT_ENTRY)]
class ChurchDeskImportEntryListener {

    public function __invoke( ImportEntryEvent $event ): void {
        // …
    }
}
```

Console Commands
--

An automatic import of all events and blog entries can be started via the command `vendor/bin/contao-console contao:churchdesk:import`.