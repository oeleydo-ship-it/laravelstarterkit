// Live count of agents currently in the inbox, driven by the tenant presence channel.
const el = document.getElementById('online-agents');

if (el && window.Echo) {
    const tenantId = el.dataset.tenantId;
    const countEl = document.getElementById('online-agents-count');
    const agents = new Map();

    const render = () => {
        countEl.textContent = agents.size;
        el.title = agents.size
            ? [...agents.values()].map((a) => a.name).join(', ')
            : 'No agents online';
    };

    window.Echo.join(`tenant.${tenantId}.agents`)
        .here((members) => {
            agents.clear();
            members.forEach((m) => agents.set(m.id, m));
            render();
        })
        .joining((member) => {
            agents.set(member.id, member);
            render();
        })
        .leaving((member) => {
            agents.delete(member.id);
            render();
        })
        .listen('.agent.availability', (data) => {
            if (agents.has(data.id)) {
                agents.set(data.id, { ...agents.get(data.id), ...data });
                render();
            }
        });
}
