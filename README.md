# PolitAnalytics AG backend test

## Task 1

0. Clone the project: [PolitAnalytics backend test repository](https://github.com/dfolgado/politanalytics-recruit-test).
1. Run this command in the CLI for install the dependencies with composer:
```
php composer install
```
2. Although an already filled DB is provided in the repository (`var/data.db`) for its verification, you can drop everything and run a new import from scratch if needed. 

For that, let's first truncate the existing DB running tests this command:

```
php bin/console doctrine:schema:drop --full-database --force
```

Then, create again the schema:

```
php bin/console doctrine:schema:create
```

And, finally run the created import command:

```
php bin/console politanalytics:import:mep
```




**Enjoy!**

## Credits

Created by [David Folgado](https://www.linkedin.com/in/david-folgado-de-la-rosa/).