<?php

namespace App\Command;

use App\Entity\Contact;
use App\Entity\ContactType;
use App\Entity\Person;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;

class ImportMEPCommand extends Command
{
    private static $MEP_URL_FULL_LIST = 'https://www.europarl.europa.eu/meps/en/full-list/xml/a';
    private static $MEP_PERSONAL_URL_TERMPLATE = 'https://www.europarl.europa.eu/meps/en/%s/%s/home';
    protected static $defaultName = 'politanalytics:import:mep';

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName("MEp Import")
            ->setDescription('Import members of the european parliament.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $xml = file_get_contents('https://www.europarl.europa.eu/meps/en/full-list/xml/a');
        $crawler = new Crawler($xml);

        foreach ($crawler->filterXPath('//mep') as $xmlNodeMep) {
            $crawlerMep = new Crawler($xmlNodeMep);

            $mep = new Person();
            $id = $crawlerMep->filterXPath('mep/id')->text();
            $mep->setId($id);
            $name = $this->splitFullName($crawlerMep->filterXPath('mep/fullName')->text());
            $mep->setFirstName($name[0]);
            $mep->setLastName($name[1]);
            $mep->setCountry($crawlerMep->filterXPath('mep/country')->text());
            $mep->setPoliticalGroup($crawlerMep->filterXPath('mep/politicalGroup')->text());
            $mep->setNationalPoliticalGroup($crawlerMep->filterXPath('mep/nationalPoliticalGroup')->text());

            $this->scrapeMEPMediaContactInfo($mep);

            $this->entityManager->persist($mep);
            $io->info(sprintf('Member with id %s imported sucessfully.', $id));
        }

        $this->entityManager->flush();

        $io->success('Import completed!!');

        return Command::SUCCESS;
    }

    /**
     * Scrapes electronic/Social media references
     * @param Person $mep
     * @return void
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function scrapeMEPMediaContactInfo(Person $mep) {
        $url = $this->buildPersonalUrl($mep);

        $html = file_get_contents($url);
        $crawler = new Crawler($html);

        // Scrapes electronic/Social media references
        foreach ($crawler->filter('div.erpl_social-share-horizontal>a') as $mediaContact) {
            $crawlerMediaContact = new Crawler($mediaContact);

            $contact = new Contact();
            $contact->setValue($crawlerMediaContact->extract(["href"])[0]);
            $contact->setType($crawlerMediaContact->text());
            $contact->setPerson($mep);

            $this->entityManager->persist($contact);

        }

        // Scrapes addresses references
        foreach ($crawler->filter('div.erpl_contact-card-list>span') as $addressContact) {
            $crawlerAddressContact = new Crawler($addressContact);

            $contact = new Contact();
            $contact->setValue($crawlerAddressContact->text());
            $contact->setType("Address");
            $contact->setPerson($mep);

            $this->entityManager->persist($contact);
        }
    }

    /**
     * Splits full name into first and last name(s)
     *
     * @param string $fullName
     * @return array
     */
    public function splitFullName(string $fullName) {
        $firstNameEndPosition = strpos($fullName, ' ');
        $name[] = substr($fullName, 0, $firstNameEndPosition);
        $name[] = substr($fullName, $firstNameEndPosition+1, strlen($fullName));

        return $name;
    }

    /**
     * Build the personal Parliament URL with the following template:
     * https://www.europarl.europa.eu/meps/en/{id}/{name}/home
     * whith {id} and {name} from the MEP. Note that the name is built following the buildUrlName method and after that
     * we encode only on non-ASCII characters.
     *
     * @param Person $mep
     * @return string
     */
    public function buildPersonalUrl(Person $mep) {

        $nameForUrl = $this->buildUrlName($mep->getFirstName(), $mep->getLastName());

        $builtUrl = sprintf($this::$MEP_PERSONAL_URL_TERMPLATE, $mep->getId(), $nameForUrl);

        return preg_replace_callback('/[^\x20-\x7f]/', function($match) {
            return urlencode($match[0]);
        }, $builtUrl);
    }

    /**
     * Format the fullname separating the first name from the last with '_' and complex last names with '+'.
     * Examples:
     *
     * Magdalena ADAMOWICZ      ->  MAGDALENA_ADAMOWICZ
     * Attila ARA-KOVÁCS        ->  ATTILA_ARA-KOVACS
     * Pablo ARIAS ECHEVERRÍA   ->  PABLO_ARIAS+ECHEVERRIA
     *
     * @param string $firstName
     * @param string $fullName
     * @return string
     */
    public function buildUrlName(string $firstName, string $fullName)
    {
        return strtoupper($firstName) . '_' . str_replace(' ', '+', $fullName);
    }
}