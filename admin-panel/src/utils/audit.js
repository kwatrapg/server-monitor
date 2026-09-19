const db = require('../db');

const insertLog = db.prepare(`
  INSERT INTO audit_log (admin_user_id, action, entity, entity_id, meta, ip)
  VALUES (@adminUserId, @action, @entity, @entityId, @meta, @ip)
`);

function logAction(req, action, entity, entityId, meta = {}) {
  insertLog.run({
    adminUserId: req.session && req.session.adminUserId ? req.session.adminUserId : null,
    action,
    entity,
    entityId: entityId || null,
    meta: JSON.stringify(meta),
    ip: req.ip || '',
  });
}

module.exports = { logAction };
