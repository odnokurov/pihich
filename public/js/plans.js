const form = document.querySelector('#plan-form');
const options = document.querySelector('#plan-options');
const plans = form.elements.plan_id;
const months = form.elements.duration_months;
const visits = form.elements.visits_per_month;
const summary = document.querySelector('#plan-summary');
const price = document.querySelector('#plan-price');

function updateQuote() {
    const plan = plans.selectedOptions[0];
    const custom = plan.dataset.kind === 'custom';
    options.hidden = !custom;
    options.disabled = !custom;
    if (custom && (!months.validity.valid || !visits.validity.valid)) {
        summary.textContent = 'Укажите от 1 до 24 тренировок в месяц и срок от 1 до 12 месяцев.';
        price.textContent = '';
        return;
    }
    const duration = Number(custom ? months.value : plan.dataset.months);
    const limit = custom ? duration * Number(visits.value) : Number(plan.dataset.visits);
    const kopecks = Number(plan.dataset.price) * (custom ? limit : 1);
    const formatted = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 }).format(kopecks / 100);
    summary.textContent = 'Срок ' + (duration ? duration + ' мес.' : '1 день') + ' · Тренировок ' + limit;
    price.textContent = formatted + ' ₽';
}

form.addEventListener('input', updateQuote);
document.querySelectorAll('.plan-choice').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        plans.value = link.dataset.plan;
        updateQuote();
        plans.focus();
    });
});
updateQuote();
