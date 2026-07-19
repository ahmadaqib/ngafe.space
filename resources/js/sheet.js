window.ngafeSheet = () => ({
    open: false,
    expanded: false,
    startY: 0,
    deltaY: 0,
    show() {
        this.open = true;
        this.expanded = false;
        document.body.classList.add('ngafe-sheet-open');
        this.$nextTick(() => this.$refs.panel?.focus());
    },
    close() {
        this.open = false;
        this.expanded = false;
        document.body.classList.remove('ngafe-sheet-open');
        this.$nextTick(() => this.$refs.trigger?.focus());
    },
    toggleSnap() {
        this.expanded = !this.expanded;
    },
    begin(event) {
        this.startY = event.clientY;
        this.deltaY = 0;
        event.currentTarget.setPointerCapture?.(event.pointerId);
    },
    move(event) {
        if (!this.startY) return;
        this.deltaY = event.clientY - this.startY;
    },
    end() {
        if (this.deltaY < -60) this.expanded = true;
        if (this.deltaY > 60) this.expanded ? this.expanded = false : this.close();
        this.startY = 0;
        this.deltaY = 0;
    },
});
