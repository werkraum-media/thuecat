<?php
declare(strict_types=1);

namespace WerkraumMedia\ThueCat\Domain\Import\Parser\Entity;

use WerkraumMedia\ThueCat\Domain\Import\Parser\DataHandlerPayload;

class TouristInformationEntity extends AbstractEntity
{
    public $table = 'tx_thuecat_tourist_information';
    protected int $priority = 20;
    protected string $remote_id = '';
    protected string $title = '';
    protected string $description = '';

    // Relations, track by their identifier
    protected string $town = '';
    protected string $managed_by = '';

    public function configure(array $node, bool $extractRelations = false)
    {
        $this->remote_id = $this->getRemoteId($node);
        $this->title = $this->extractLanguageValue($node['schema:name'] ?? null);
        $this->description = $this->extractLanguageValue($node['schema:description'] ?? null);
        if ($extractRelations === true) {
            // @todo [1] implement relation extraction, if this is the top level entity.
            // @todo [2] For now, we skip, as everything comes from elsewhere (TouristAttraction mostly)
        }

    }

    public function handlesTypes(): array
    {
        return ['thuecat:TouristInformation'];
    }
}