/**
 * Bug Tracking System (BugTrace) - Standalone Zero-Dependency Chart Engine
 * Renders smooth Canvas charts offline without external libraries
 */

const ChartEngine = {
    /**
     * Render a sleek donut chart on a canvas element
     * @param {string} canvasId 
     * @param {Array<{label: string, value: number, color: string}>} data 
     */
    renderDonut(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;

        // Adjust for high-DPI displays
        const width = canvas.parentElement.clientWidth || 320;
        const height = canvas.parentElement.clientHeight || 260;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.scale(dpr, dpr);

        const total = data.reduce((sum, item) => sum + item.value, 0);
        if (total === 0) {
            ctx.fillStyle = '#94a3b8';
            ctx.font = '14px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('No data available', width / 2, height / 2);
            return;
        }

        const centerX = width / 2;
        const centerY = height / 2;
        const radius = Math.min(centerX, centerY) - 20;
        const innerRadius = radius * 0.62;

        let startAngle = -Math.PI / 2;

        data.forEach(item => {
            if (item.value <= 0) return;
            const sliceAngle = (item.value / total) * 2 * Math.PI;
            const endAngle = startAngle + sliceAngle;

            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, startAngle, endAngle);
            ctx.arc(centerX, centerY, innerRadius, endAngle, startAngle, true);
            ctx.closePath();
            ctx.fillStyle = item.color;
            ctx.fill();

            startAngle = endAngle;
        });

        // Center total label
        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 24px -apple-system, BlinkMacSystemFont, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(total.toString(), centerX, centerY - 6);

        ctx.fillStyle = '#64748b';
        ctx.font = '11px -apple-system, BlinkMacSystemFont, sans-serif';
        ctx.fillText('TOTAL BUGS', centerX, centerY + 16);
    },

    /**
     * Render a clean horizontal or vertical bar chart
     * @param {string} canvasId 
     * @param {Array<{label: string, value: number, color: string}>} data 
     */
    renderBar(canvasId, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;

        const width = canvas.parentElement.clientWidth || 400;
        const height = canvas.parentElement.clientHeight || 260;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.scale(dpr, dpr);

        if (data.length === 0) {
            ctx.fillStyle = '#94a3b8';
            ctx.font = '14px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('No data available', width / 2, height / 2);
            return;
        }

        const maxVal = Math.max(...data.map(d => d.value), 1);
        const paddingLeft = 90;
        const paddingRight = 40;
        const paddingTop = 25;
        const paddingBottom = 20;
        const chartWidth = width - paddingLeft - paddingRight;
        const chartHeight = height - paddingTop - paddingBottom;
        const barHeight = Math.min(26, chartHeight / data.length - 12);
        const gap = chartHeight / data.length;

        data.forEach((item, index) => {
            const y = paddingTop + index * gap + (gap - barHeight) / 2;
            const barWidth = Math.max(6, (item.value / maxVal) * chartWidth);

            // Label
            ctx.fillStyle = '#334155';
            ctx.font = '500 12px -apple-system, BlinkMacSystemFont, sans-serif';
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';
            ctx.fillText(item.label, paddingLeft - 10, y + barHeight / 2);

            // Background track
            ctx.fillStyle = '#f1f5f9';
            ctx.beginPath();
            ctx.roundRect(paddingLeft, y, chartWidth, barHeight, 4);
            ctx.fill();

            // Active bar
            ctx.fillStyle = item.color || '#2563eb';
            ctx.beginPath();
            ctx.roundRect(paddingLeft, y, barWidth, barHeight, 4);
            ctx.fill();

            // Value text
            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 12px -apple-system, BlinkMacSystemFont, sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(item.value.toString(), paddingLeft + barWidth + 8, y + barHeight / 2);
        });
    }
};
