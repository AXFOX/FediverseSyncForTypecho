# Fediverse Sync for Typecho - 更新日志

## 版本 1.6.5 (2026-06-18)

### 新增功能
- **SOCKS5/HTTP 代理支持**
  - 新增可选代理配置，支持 HTTP 和 SOCKS5 两种代理类型
  - SOCKS5 使用远端 DNS 解析（`CURLPROXY_SOCKS5_HOSTNAME`），避免 DNS 污染
  - 支持代理认证（用户名/密码）
  - 适用于中国大陆等网络受限环境

### 重构优化
- **HTTP 请求统一重构**
  - 将分散在 Plugin.php、Action.php、Api/Sync.php 中的 6 处原始 cURL 调用集中到 `Utils/Http.php`
  - 新增 `postForm()` 方法，统一处理 Mastodon/GoToSocial 的表单编码 POST 请求
  - Header 去重处理，避免重复 header 导致 400 错误
  - 代理逻辑由 `Utils/Proxy.php` 集中管理，一处配置全局生效

### 调试改进
- **增强 HTTP 层错误日志**
  - 请求失败时自动记录 URL、HTTP 状态码、cURL 错误号和错误描述、响应体预览
  - Proxy 应用代理时记录代理类型和地址，便于确认代理是否生效

### 文件结构
```
FediverseSync/
├── Utils/
│   ├── Proxy.php           # 增强：支持 SOCKS5+HTTP 代理类型选择
│   └── Http.php            # 增强：新增 postForm() + 代理集成 + 日志增强
└── Plugin.php              # 新增5个代理配置项
```

### 行为调整
- `{content}` 使用文章原始 Markdown 文本（保留换行等结构），不再做 HTML 去标签与空白压缩
- 模板变量 `title` / `author` / `site_name` 会进行 HTML 实体解码，避免出现 `&amp;` 这类重复转义显示

## 版本 1.6.3 (2025-12-19)

### 修复问题
- “原文内容长度限制”改为严格截断（包含省略号在内），避免长度计算偏差
- Mastodon/GoToSocial 同步时，正文过长会自动截断以避免发布失败

## 版本 1.6.2 (2025-12-19)

### 行为调整
- 移除数据库日志表写入：不再写入 `{前缀}fediverse_sync_logs`
- 日志改为写入 `usr/logs/fediverse-sync.log`（错误日志始终记录，调试模式记录更多信息）

## 版本 1.6.1 (2025-12-19)

### 行为调整
- 移除“同步时显示原文内容”配置项：是否包含原文内容改为由同步内容模板是否包含 `{content}` 决定
- “原文内容长度限制”仅在模板包含 `{content}` 时生效

### 修复问题
- 修复部分场景下同步消息无法带入正文内容的问题（发布同步 / 手动同步 / API 同步）
- 修复 SQLite 环境下启用插件时，建表检测查询 `information_schema` 导致的报错

## 版本 1.6.0 (2024-01-15)

### 新增功能
- **自定义同步内容模板**
  - 支持6个模板变量：{title}、{permalink}、{content}、{author}、{created}、{site_name}
  - 可完全自定义同步到Fediverse的消息格式
  - 模板验证功能，确保变量使用正确

- **原文内容显示选项**
  - 新增"同步时显示原文内容"配置选项
  - 可设置原文内容长度限制（0表示不限制）
  - 自动去除HTML标签并格式化内容

- **后台管理界面优化**
  - 新增同步配置信息预览面板
  - 显示当前实例类型、可见性、模板等配置
  - 提供模板变量使用说明
  - 美化界面样式，提升用户体验

### 代码优化
- **模块化架构**
  - 新增 `Utils/Template.php` 模板工具类
  - 统一处理模板解析、内容处理、变量验证
  - 提高代码复用性和可维护性

- **改进的内容处理**
  - 智能HTML标签去除
  - 空白字符规范化处理
  - 内容长度智能截断

- **同步逻辑优化**
  - 所有同步入口统一使用模板系统
  - 支持自动同步和手动同步两种模式
  - 改进错误处理和日志记录

### 文件结构优化
```
FediverseSync/
├── Plugin.php              # 主插件文件（新增模板配置）
├── Action.php              # 手动同步操作（集成模板系统）
├── Api/Sync.php            # API同步功能（集成模板系统）
├── panel.php               # 后台面板（新增配置预览）
├── Models/Binding.php      # 数据模型（保持不变）
├── Utils/
│   ├── Http.php            # HTTP请求工具（保持不变）
│   └── Template.php        # 新增模板工具类
└── README.md               # 更新文档说明
```

### 使用示例

#### 默认模板
```
「{title}」

{permalink}

From「{site_name}」
```

#### 自定义模板示例
```
📢 {site_name} 发布了新文章「{title}」

{content}

👉 {permalink}

作者：{author} | {created}
```

#### 简洁模板
```
新文章：{title} {permalink}
```

### 兼容性
- 完全向后兼容，升级不会影响现有功能
- 默认配置保持原有行为
- 新功能均为可选配置

### 修复问题
- 优化了内容处理的性能
- 改进了错误处理机制
- 增强了代码的可读性和可维护性
