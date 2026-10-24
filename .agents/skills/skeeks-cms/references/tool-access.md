# MCP / REST tool discovery and access

Use this workflow for a requested inventory check, newly deployed tools, or
different tool availability between transports. Do not make it a preflight
for ordinary known reads.

## Compare authorized inventories

- For MCP, use the connected server's authorized `tools/list` inventory.
  Cache by `skeeks/toolsRevision`; inspect only relevant descriptors.
- For REST, use the canonical `skeeks-rest.ps1 -Action tools-index
  -CacheMaxAgeSeconds 0`. Parse the output in memory and report a compact
  summary. Fetch one schema with `-Action tool-schema -ToolName <name>`.
- Compare exact tool names and `required_scope`, recording `server_version`
  and `tools_revision`. A total count alone does not establish parity.
  Installed packages, RBAC and OAuth grants can all affect visibility.
- OAuth sessions for `/cms/mcp` and `/cms/rest-api` are separate. Logging
  into MCP does not upgrade REST permissions. Use the canonical REST login
  client for REST, and the configured host's MCP OAuth login for MCP.
  In Codex CLI, `codex mcp login <configured-server-name>` uses the existing
  server configuration; do not invent a second server or authorization flow.
- After login, inspect the current tool registry again. If the MCP server
  remains absent, reopen the chat or restart the client; do not assume every
  host can refresh its registry during a turn.

Expand OAuth grants only within the user's authorized scope. A missing tool
alone is not permission to add unrelated write access. Do not repeat login
when the required tool and scope are already available. Never print or copy
access tokens, refresh tokens, client secrets or stored credentials.

## Verify without side effects

Check availability through the authorized catalog, relevant individual schemas
and a few bounded reads. Report the distinction between catalog visibility,
schema retrieval and actual execution. Never execute every write tool simply
to test permissions; an inventory check does not authorize changing site or CRM
records, sending messages or launching campaigns.

If the full REST `-Action tools` response fails to parse but `tools-index` and
individual `tool-schema` requests work, continue with those compact endpoints.
Report the bulk-schema failure separately; do not treat it as expired OAuth
or claim the root cause is known without investigating it.

## Useful families

- `cms_crm_client_overview_list`, `cms_crm_client_overview_get`: client selection
  and summary (`cms.company.read`); `cms_crm_history_get`: history
  (`cms.activity.read`).
- `cms_comm_message_list`, `cms_comm_message_get`, `cms_comm_conversation_get`:
  correspondence; `cms_comm_account_list`, `cms_comm_topic_list`,
  `cms_comm_mailing_check`, `cms_comm_contact_state_get`: mailbox access,
  mailing topics and subscription checks (`cms.communication.read`).
- `cms_proposal_list`, `cms_proposal_get`, `cms_proposal_library_get`: offers,
  ready presentations and templates (`cms.communication.read`).
- `cms_comm_draft_create`, `cms_comm_campaign_create`,
  `cms_comm_campaign_recipient_add`: prepare drafts (`cms.communication.send`).
  This scope does not provide a campaign launch tool. Follow
  [crm-clients-and-offers.md](crm-clients-and-offers.md) for dry runs,
  confirmations, per-recipient refusals and employee sending.
- `cms_theme_update`: `cms.theme.write`;
  `cms_component_settings_update`: `cms.settings.write`.

For Form2 submission changes, resolve statuses through
`form2_form_send_status_list`. For field types and social-link types use
`form2_form_property_component_list`, `cms_content_property_component_list`
and `cms_site_social_type_list`. If such a helper is absent from MCP, check
its availability in the target REST catalog before concluding it is missing
from the server. Do not guess type identifiers.

Tool counts and revisions are diagnostics for a particular session, not stable
requirements. Do not hard-code the observed counts as future acceptance tests.
