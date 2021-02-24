#!/usr/bin/env bash
set -eu

env_file='.env'
env_testing_file='.env.testing'

if [ -f $env_testing_file ]; then
    file=$env_testing_file
else
    file=$env_file
fi

# 判断 env 中的配置是否在白名单内
# $1 env 文件名
# $2 要检查的变量名
# $3 白名单数组
function check_env_in_whitelist() {
    if [[ $# -lt 3 ]]; then
        echo "$0 need at least 3 parametes"
        exit 1
    fi
    #读取前两个参数
    local file_name="$1"
    local name="$2"
    #删除前两个参数
    shift 2
    #读取所有参数作为数组
    local whitelists=("$@")
    #获取配置文件中的值
    local value
    value=$(grep <"${file_name}" -E "^\b${name}\b" | awk -F '=' '{print $2}')
    #检查白名单
    if ! printf '%s\n' "${whitelists[@]}" | grep -q -P "^${value}$"; then
        read -r -p "${name} [${value}] is not in whitelists, you may delete/modify unexpected values, continue? (yes/no) [no]:" anwser
        if [ "$anwser" != 'yes' ]; then
            exit 1
        fi
    fi
}

# check db
db_host_whitelists=("mysql" "localhost" "127.0.0.1" "0.0.0.0")
db_host_name=DB_HOST
check_env_in_whitelist $file $db_host_name "${db_host_whitelists[@]}"

# check es
es_host_whitelists=("elasticsearch" "localhost" "127.0.0.1" "0.0.0.0")
es_host_name=ELASTIC_HOST
check_env_in_whitelist $file $es_host_name "${es_host_whitelists[@]}"

# check redis
redis_host_whitelists=("redis" "localhost" "127.0.0.1" "0.0.0.0")
redis_host_name=REDIS_HOST
check_env_in_whitelist $file $redis_host_name "${redis_host_whitelists[@]}"
