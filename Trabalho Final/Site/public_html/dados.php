<?php
/*
 * dados.php - É AQUI que você edita o conteúdo da página (nomes, descrições, links, projetos).
 * Link vazio ('') = o ícone não aparece. Não precisa mexer no index.php.
 *
 * Fotos: coloque a imagem na pasta fotos/ e escreva o nome em 'foto' (ex.: 'bernardo.jpg').
 *        Sem foto, a página mostra um círculo colorido com as iniciais.
 * Ícones disponíveis em 'links': instagram, github, linkedin, lattes, site, email
 */
return [

    'pessoas' => [
        [
            'nome'      => 'Amandha Veiga',
            'papel'     => 'Hardware e documentação',
            'descricao' => 'Estudante do TDS no IFSC Câmpus Chapecó.',
            'foto'      => '',
            'cor'       => '#c2410c',
            'links'     => ['instagram' => '', 'github' => 'https://github.com/AmandhaVeiga81', 'linkedin' => '', 'lattes' => '', 'site' => '', 'email' => ''],
        ],
        [
            'nome'      => 'Bernardo Lamperti',
            'papel'     => 'Montagem do hardware',
            'descricao' => 'Estudante do TDS no IFSC Câmpus Chapecó.',
            'foto'      => 'bernardo.jpg',
            'cor'       => '#0f6b4f',
            'links'     => ['instagram' => 'https://instagram.com/bernardoxky', 'github' => 'https://github.com/bernardoxky', 'linkedin' => '', 'lattes' => 'http://lattes.cnpq.br/0568328542592678', 'site' => 'https://bernardolamperti.com.br/', 'email' => 'lampertib90@gmail.com',],
        ],
        [
            'nome'      => 'Deidiani Bucoski',
            'papel'     => 'Estudante de TDS',
            'descricao' => 'Estudante de TDS no IFSC Câmpus Chapecó.',
            'foto'      => '',
            'cor'       => '#7c3aed',
            'links'     => ['instagram' => '', 'github' => 'https://github.com/deidianib', 'linkedin' => '', 'lattes' => '', 'site' => '', 'email' => ''],
        ],
        [
            'nome'      => 'Guilherme Chimek',
            'papel'     => 'Estudante de TDS',
            'descricao' => 'Estudante de TDS no IFSC Câmpus Chapecó.',
            'foto'      => '',
            'cor'       => '#1d6fb8',
            'links'     => ['instagram' => '', 'github' => 'https://github.com/Guilherme-chimek', 'linkedin' => '', 'lattes' => '', 'site' => '', 'email' => ''],
        ],
        [
            'nome'      => 'Pedro Sudatti',
            'papel'     => 'Montagem do hardware',
            'descricao' => 'Estudante do TDS no IFSC Câmpus Chapecó.',
            'foto'      => '',
            'cor'       => '#b4234a',
            'links'     => ['instagram' => '', 'github' => 'https://github.com/PH-Sudatti', 'linkedin' => '', 'lattes' => '', 'site' => '', 'email' => ''],
        ],
        [
            'nome'      => 'Valentina Povala',
            'papel'     => 'Banco de dados',
            'descricao' => 'Estudante do TDS no IFSC Câmpus Chapecó..',
            'foto'      => '',
            'cor'       => '#0e7490',
            'links'     => ['instagram' => '', 'github' => 'https://github.com/Valen-pv', 'linkedin' => '', 'lattes' => 'http://lattes.cnpq.br/2218732576111767', 'site' => '', 'email' => ''],
        ],
    ],

    'projetos' => [
        [
            'titulo'     => 'Sensor de Proximidade',
            'disciplina' => 'Programação IV · Trabalho final',
            'icone'      => 'chip',
            'descricao'  => 'Sistema embarcado que mede a distância de objetos com um sensor ultrassônico e avisa em etapas: o display mostra uma mensagem, o LED RGB muda de cor e o buzzer apita. As mensagens e as faixas de distância são programadas por um painel web, e as leituras ficam salvas num banco de dados.',
            'tags'       => ['ESP8266', 'C++', 'PHP', 'MySQL', 'HTML/CSS'],
            'membros'    => ['Amandha Veiga', 'Bernardo Lamperti', 'Pedro Sudatti', 'Valentina Povala'],
            'links'      => [
                ['rotulo' => 'Abrir o controle', 'url' => '/painel.php', 'icone' => 'painel', 'principal' => true],
                ['rotulo' => 'Código no GitHub', 'url' => '', 'icone' => 'github'],
            ],
        ],
        // Para adicionar outro projeto, copie o bloco acima, cole aqui e edite.
    ],

];
