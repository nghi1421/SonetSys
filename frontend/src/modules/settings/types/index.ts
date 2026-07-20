export type CacheDriver = 'database' | 'redis'
export type RedisClient = 'predis' | 'phpredis'

export interface SystemSettings {
  cache_driver: CacheDriver
  redis_client: RedisClient
  redis_host: string
  redis_port: number
  redis_database: number
  has_redis_password: boolean
  mail_host: string
  mail_port: number
  mail_username: string | null
  mail_encryption: string | null
  mail_from_address: string
  mail_from_name: string
  has_mail_password: boolean
  max_upload_size_kb: number
}

export interface UpdateSystemSettingsPayload {
  cache_driver: CacheDriver
  redis_client: RedisClient
  redis_host: string
  redis_port: number
  redis_password?: string
  redis_database: number
  mail_host: string
  mail_port: number
  mail_username?: string
  mail_password?: string
  mail_encryption?: string
  mail_from_address: string
  mail_from_name: string
  max_upload_size_kb: number
}
