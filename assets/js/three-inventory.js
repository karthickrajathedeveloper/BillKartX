/**
 * Three.js 3D Warehouse & Inventory Visualization Engine
 * Interactive 3D racks, product items, stock levels & 3D analytics
 */

class Warehouse3D {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.options = Object.assign({
            onProductClick: null,
            interactive: true,
            theme: 'dark'
        }, options);

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();
        this.interactiveMeshes = [];
        this.hoveredMesh = null;
        this.data = null;

        this.init();
    }

    init() {
        const width = this.container.clientWidth || 800;
        const height = this.container.clientHeight || 500;

        // 1. Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0f172a); // Slate-900

        // 2. Camera
        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        this.camera.position.set(0, 18, 28);

        // 3. Renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        // Clear existing canvas if any
        this.container.innerHTML = '';
        this.container.appendChild(this.renderer.domElement);

        // 4. Orbit Controls
        if (typeof THREE.OrbitControls !== 'undefined') {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            this.controls.maxPolarAngle = Math.PI / 2.05; // Don't go below floor
            this.controls.minDistance = 5;
            this.controls.maxDistance = 60;
        }

        // 5. Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
        this.scene.add(ambientLight);

        const dirLight = new THREE.DirectionalLight(0xffffff, 0.9);
        dirLight.position.set(15, 25, 20);
        dirLight.castShadow = true;
        dirLight.shadow.mapSize.width = 1024;
        dirLight.shadow.mapSize.height = 1024;
        this.scene.add(dirLight);

        const blueAccent = new THREE.PointLight(0x38bdf8, 0.5, 30);
        blueAccent.position.set(-15, 10, -5);
        this.scene.add(blueAccent);

        // 6. Grid / Warehouse Floor
        const floorGeo = new THREE.PlaneGeometry(60, 40);
        const floorMat = new THREE.MeshStandardMaterial({
            color: 0x1e293b,
            roughness: 0.8,
            metalness: 0.2
        });
        const floor = new THREE.Mesh(floorGeo, floorMat);
        floor.rotation.x = -Math.PI / 2;
        floor.receiveShadow = true;
        this.scene.add(floor);

        const gridHelper = new THREE.GridHelper(60, 30, 0x334155, 0x1e293b);
        gridHelper.position.y = 0.01;
        this.scene.add(gridHelper);

        // 7. Event Listeners
        window.addEventListener('resize', () => this.onResize());

        if (this.options.interactive) {
            this.renderer.domElement.addEventListener('mousemove', (e) => this.onMouseMove(e));
            this.renderer.domElement.addEventListener('click', (e) => this.onClick(e));
        }

        // Start Animation Loop
        this.animate = this.animate.bind(this);
        requestAnimationFrame(this.animate);
    }

    onResize() {
        if (!this.container || !this.renderer || !this.camera) return;
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;
        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    }

    animate() {
        requestAnimationFrame(this.animate);
        if (this.controls) this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }

    /**
     * Load Shelves and Products Data
     */
    loadShelves(shelvesData) {
        this.data = shelvesData;
        this.interactiveMeshes = [];

        // Remove previous dynamic meshes
        const toRemove = [];
        this.scene.traverse((child) => {
            if (child.userData && child.userData.isShelfElement) {
                toRemove.push(child);
            }
        });
        toRemove.forEach(obj => this.scene.remove(obj));

        if (!shelvesData || shelvesData.length === 0) return;

        const shelfSpacingX = 6.5;
        const startX = -((shelvesData.length - 1) * shelfSpacingX) / 2;

        shelvesData.forEach((categoryShelf, shelfIndex) => {
            const posX = startX + (shelfIndex * shelfSpacingX);
            this.buildShelfRack(posX, categoryShelf);
        });
    }

    /**
     * Build individual 3D Rack for Category
     */
    buildShelfRack(posX, category) {
        const rackWidth = 5.0;
        const rackHeight = 8.5;
        const rackDepth = 1.8;
        const levels = 3;

        // Metal frame material
        const frameMat = new THREE.MeshStandardMaterial({
            color: 0x475569,
            metalness: 0.7,
            roughness: 0.3
        });

        // 4 Vertical Pillars
        const pillarGeo = new THREE.BoxGeometry(0.12, rackHeight, 0.12);
        const offsets = [
            [-rackWidth / 2, -rackDepth / 2],
            [rackWidth / 2, -rackDepth / 2],
            [-rackWidth / 2, rackDepth / 2],
            [rackWidth / 2, rackDepth / 2]
        ];

        offsets.forEach(([ox, oz]) => {
            const pillar = new THREE.Mesh(pillarGeo, frameMat);
            pillar.position.set(posX + ox, rackHeight / 2, oz);
            pillar.castShadow = true;
            pillar.userData.isShelfElement = true;
            this.scene.add(pillar);
        });

        // Category Signboard on top
        const signGeo = new THREE.BoxGeometry(rackWidth, 0.6, 0.1);
        const signColor = category.color ? parseInt(category.color.replace('#', '0x')) : 0x2563eb;
        const signMat = new THREE.MeshStandardMaterial({
            color: signColor,
            roughness: 0.4
        });
        const sign = new THREE.Mesh(signGeo, signMat);
        sign.position.set(posX, rackHeight + 0.3, rackDepth / 2);
        sign.userData.isShelfElement = true;
        this.scene.add(sign);

        // Shelves planks & Products
        const plankGeo = new THREE.BoxGeometry(rackWidth, 0.08, rackDepth);
        const plankMat = new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.4, roughness: 0.5 });

        const products = category.products || [];
        const itemsPerLevel = Math.ceil(products.length / levels) || 1;

        for (let lvl = 0; lvl < levels; lvl++) {
            const levelY = 1.2 + (lvl * 2.5);

            // Plank
            const plank = new THREE.Mesh(plankGeo, plankMat);
            plank.position.set(posX, levelY, 0);
            plank.receiveShadow = true;
            plank.userData.isShelfElement = true;
            this.scene.add(plank);

            // Place products on this level
            const levelProducts = products.slice(lvl * 3, (lvl * 3) + 3);
            const slotSpacing = rackWidth / (levelProducts.length + 1);

            levelProducts.forEach((prod, itemIdx) => {
                const prodX = posX - (rackWidth / 2) + ((itemIdx + 1) * slotSpacing);
                const prodY = levelY + 0.45;
                const prodZ = 0;

                this.createProductMesh(prod, prodX, prodY, prodZ, category.name);
            });
        }
    }

    /**
     * Create interactive 3D box representing product
     */
    createProductMesh(product, x, y, z, categoryName) {
        // Dimensions based on unit
        let w = 0.8, h = 0.8, d = 0.8;
        if (product.base_unit === 'l' || product.base_unit === 'ml') {
            // Bottle cylinder style or tall box
            w = 0.6; h = 1.0; d = 0.6;
        } else if (product.base_unit === 'pcs') {
            w = 0.7; h = 0.5; d = 0.7;
        }

        const geom = new THREE.BoxGeometry(w, h, d);

        // Color based on stock status
        let colorHex = 0x10b981; // Green (> min stock)
        if (product.stock_qty <= 0) {
            colorHex = 0xef4444; // Red (Out of stock)
        } else if (product.stock_qty <= product.min_stock_alert) {
            colorHex = 0xf59e0b; // Amber / Yellow (Low stock)
        }

        const mat = new THREE.MeshStandardMaterial({
            color: colorHex,
            roughness: 0.4,
            metalness: 0.1
        });

        const mesh = new THREE.Mesh(geom, mat);
        mesh.position.set(x, y, z);
        mesh.castShadow = true;
        mesh.receiveShadow = true;
        mesh.userData = {
            isShelfElement: true,
            isProduct: true,
            productData: product,
            categoryName: categoryName,
            originalColor: colorHex
        };

        this.scene.add(mesh);
        this.interactiveMeshes.push(mesh);
    }

    onMouseMove(event) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        this.mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        this.mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.mouse, this.camera);
        const intersects = this.raycaster.intersectObjects(this.interactiveMeshes);

        if (intersects.length > 0) {
            const hit = intersects[0].object;
            if (this.hoveredMesh !== hit) {
                if (this.hoveredMesh) {
                    this.hoveredMesh.material.emissive.setHex(0x000000);
                }
                this.hoveredMesh = hit;
                this.hoveredMesh.material.emissive.setHex(0x334155);
                this.container.style.cursor = 'pointer';

                // Show floating tooltip
                this.showTooltip(event.clientX, event.clientY, hit.userData.productData, hit.userData.categoryName);
            }
        } else {
            if (this.hoveredMesh) {
                this.hoveredMesh.material.emissive.setHex(0x000000);
                this.hoveredMesh = null;
                this.container.style.cursor = 'default';
                this.hideTooltip();
            }
        }
    }

    onClick(event) {
        if (!this.hoveredMesh) return;
        const prod = this.hoveredMesh.userData.productData;
        if (typeof this.options.onProductClick === 'function') {
            this.options.onProductClick(prod);
        }
    }

    showTooltip(x, y, product, category) {
        let tip = document.getElementById('threeTooltip');
        if (!tip) {
            tip = document.createElement('div');
            tip.id = 'threeTooltip';
            tip.style.position = 'fixed';
            tip.style.zIndex = '999';
            tip.style.pointerEvents = 'none';
            tip.style.background = 'rgba(15, 23, 42, 0.9)';
            tip.style.color = '#fff';
            tip.style.padding = '8px 12px';
            tip.style.borderRadius = '6px';
            tip.style.fontSize = '12px';
            tip.style.backdropFilter = 'blur(4px)';
            tip.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
            tip.style.border = '1px solid rgba(255,255,255,0.15)';
            document.body.appendChild(tip);
        }

        const stockColor = product.stock_qty <= 0 ? '#ef4444' : (product.stock_qty <= product.min_stock_alert ? '#f59e0b' : '#10b981');

        tip.innerHTML = `
            <div style="font-weight: bold; font-size: 13px;">${product.name}</div>
            <div style="color: #94a3b8; font-size: 11px;">${category}</div>
            <div style="margin-top: 4px; display: flex; justify-content: space-between; gap: 15px;">
                <span>Price: <strong>₹${product.selling_price}/${product.base_unit}</strong></span>
                <span style="color: ${stockColor}; font-weight: bold;">Stock: ${product.stock_qty} ${product.base_unit}</span>
            </div>
            <div style="font-size: 10px; color: #38bdf8; margin-top: 4px;">Click to select / add to bill</div>
        `;

        tip.style.left = (x + 15) + 'px';
        tip.style.top = (y + 15) + 'px';
        tip.style.display = 'block';
    }

    hideTooltip() {
        const tip = document.getElementById('threeTooltip');
        if (tip) tip.style.display = 'none';
    }

    resetCamera(viewMode = 'overview') {
        if (!this.camera || !this.controls) return;
        if (viewMode === 'overview') {
            this.camera.position.set(0, 18, 28);
            this.controls.target.set(0, 4, 0);
        } else if (viewMode === 'top') {
            this.camera.position.set(0, 35, 1);
            this.controls.target.set(0, 0, 0);
        } else if (viewMode === 'front') {
            this.camera.position.set(0, 6, 20);
            this.controls.target.set(0, 4, 0);
        }
        this.controls.update();
    }
}

/**
 * Three.js 3D Interactive Sales & Profit Chart
 */
class ThreeChart3D {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.init();
    }

    init() {
        const width = this.container.clientWidth || 600;
        const height = this.container.clientHeight || 350;

        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0f172a);

        this.camera = new THREE.PerspectiveCamera(40, width / height, 0.1, 1000);
        this.camera.position.set(12, 14, 18);

        this.renderer = new THREE.WebGLRenderer({ antialias: true });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;

        this.container.innerHTML = '';
        this.container.appendChild(this.renderer.domElement);

        if (typeof THREE.OrbitControls !== 'undefined') {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            this.controls.target.set(0, 3, 0);
        }

        const amb = new THREE.AmbientLight(0xffffff, 0.8);
        this.scene.add(amb);

        const dir = new THREE.DirectionalLight(0xffffff, 0.9);
        dir.position.set(10, 20, 15);
        dir.castShadow = true;
        this.scene.add(dir);

        // Platform
        const base = new THREE.Mesh(
            new THREE.BoxGeometry(20, 0.4, 14),
            new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.6 })
        );
        base.position.y = -0.2;
        base.receiveShadow = true;
        this.scene.add(base);

        window.addEventListener('resize', () => {
            if (!this.container || !this.renderer) return;
            const w = this.container.clientWidth;
            const h = this.container.clientHeight;
            this.camera.aspect = w / h;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(w, h);
        });

        const animate = () => {
            requestAnimationFrame(animate);
            if (this.controls) this.controls.update();
            this.renderer.render(this.scene, this.camera);
        };
        animate();
    }

    renderBars(dailyData) {
        if (!dailyData || dailyData.length === 0) return;

        // Clear old bars
        const toRemove = [];
        this.scene.traverse(c => {
            if (c.userData && c.userData.isChartBar) toRemove.push(c);
        });
        toRemove.forEach(b => this.scene.remove(b));

        const maxVal = Math.max(...dailyData.map(d => parseFloat(d.sales_amount) || 100), 100);
        const barWidth = 1.2;
        const spacing = 2.2;
        const startX = -((dailyData.length - 1) * spacing) / 2;

        dailyData.forEach((day, i) => {
            const val = parseFloat(day.sales_amount) || 0;
            const barHeight = Math.max(0.2, (val / maxVal) * 8.0);
            const x = startX + (i * spacing);

            // Revenue Bar (Blue)
            const geo = new THREE.BoxGeometry(barWidth, barHeight, barWidth);
            const mat = new THREE.MeshStandardMaterial({
                color: 0x2563eb,
                roughness: 0.3,
                metalness: 0.2
            });
            const bar = new THREE.Mesh(geo, mat);
            bar.position.set(x, barHeight / 2, 0);
            bar.castShadow = true;
            bar.userData.isChartBar = true;
            this.scene.add(bar);

            // Profit Bar behind it (Emerald)
            const profitVal = parseFloat(day.estimated_profit) || 0;
            const profitHeight = Math.max(0.1, (profitVal / maxVal) * 8.0);
            const pGeo = new THREE.BoxGeometry(barWidth, profitHeight, barWidth * 0.6);
            const pMat = new THREE.MeshStandardMaterial({
                color: 0x10b981,
                roughness: 0.3
            });
            const pBar = new THREE.Mesh(pGeo, pMat);
            pBar.position.set(x, profitHeight / 2, -1.5);
            pBar.castShadow = true;
            pBar.userData.isChartBar = true;
            this.scene.add(pBar);
        });
    }
}

window.Warehouse3D = Warehouse3D;
window.ThreeChart3D = ThreeChart3D;

