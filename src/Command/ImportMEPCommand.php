<?php

namespace App\Command;

use App\Entity\MemberEuropeanParliament;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;

class ImportMEPCommand extends Command
{
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
        $crawler = new Crawler();
        $crawler->addXmlContent($xml);

        foreach ($crawler->filterXPath('//mep') as $xmlNodeMep) {
            $crawlerMep = new Crawler($xmlNodeMep);

            $mep = new MemberEuropeanParliament();
            $id = $crawlerMep->filterXPath('mep/id')->text();
            $mep->setId($id);
            $mep->setFullName($crawlerMep->filterXPath('mep/fullName')->text());
            $mep->setCountry($crawlerMep->filterXPath('mep/country')->text());
            $mep->setPoliticalGroup($crawlerMep->filterXPath('mep/politicalGroup')->text());
            $mep->setNationalPoliticalGroup($crawlerMep->filterXPath('mep/nationalPoliticalGroup')->text());

            $this->entityManager->persist($mep);
            $io->info(sprintf('Member with id %s imported sucessfully.', $id));
        }

        $this->entityManager->flush();

        $io->success('Import completed!!');

        return Command::SUCCESS;
    }
}