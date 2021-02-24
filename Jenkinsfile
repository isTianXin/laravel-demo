node {
    checkout([
        $class: 'GitSCM',
        branches: [[name: env.GIT_BUILD_REF]],
        userRemoteConfigs: [[url: env.GIT_REPO_URL, credentialsId: env.CREDENTIALS_ID]]
    ])
    docker.image('mysql:5.7').withRun('-e "MYSQL_ROOT_PASSWORD=my-secret-pw" -e "MYSQL_DATABASE=test"') { c ->
        docker.image('mysql:5.7').inside("--link ${c.id}:db") {
            /* Wait until mysql service is up */
            sh 'while ! mysqladmin ping -hdb --silent; do sleep 1; done'
        }
        docker.image('sinkcup/laravel-demo:6-dev').inside("--link ${c.id}:db") {
            /*
             * Run some tests which require MySQL, and assume that it is
             * available on the host name `db`
             */
            stage('prepare') {
                echo 'preparing...'
                sh 'composer install'
                echo 'prepare done.'
            }
            stage('test') {
                echo 'testing...'
                sh './lint.sh'
                sh './phpunit.sh'
                sh 'php artisan l5-swagger:generate'
                echo 'test done.'
            }
            stage('deploy') {
                when {
                    branch 'master'
                }
                echo 'deploying docs...'
                sh ("curl -X POST -H \"Authorization: token $DEPLOY_TOKEN_DOCS\" -H 'Accept:application/json' \
                     https://tuhu.coding.net/api-docs/open/api/v1/projects/hushuo/docs/1/releases \
                     -F 'filename=@storage/api-docs/api-docs.json'")
                echo 'deploy docs done.'
            }
        }
    }
}
