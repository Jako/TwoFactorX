const fs = require('fs').promises;
const path = require('path');
const { minify } = require('terser');
const sass = require('sass');
const postcss = require('postcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');

// Read package information dynamically from package.json
const packageJson = require('./package.json');
const version = packageJson.version;
const phpversion = packageJson.phpversion;
const modxversion = packageJson.modxversion;
const currentYear = new Date().getFullYear();
const startYear = parseInt(packageJson.startYear) || currentYear;
const yearRange = currentYear > startYear ? `${startYear}-${currentYear}` : `${startYear}`;
const dateStr = new Date().toISOString()
    .split('T')[0];

const copyrightRegex = new RegExp(`Copyright ${startYear}(-\\d{4})? by`, 'g');
const copyrightReplace = `Copyright ${yearRange} by`;
const apiRegex = new RegExp(`&copy; ${startYear}(-\\d{4})?`, 'g');
const apiReplace = `&copy; ${yearRange}`;
const banner = `/*!\n * ${packageJson.fullname} - ${packageJson.description}\n * Version: ${packageJson.version}\n * Build date: ${dateStr}\n */\n`;

let versionParts = version.split('-');
let versionNumber = versionParts[0];
let versionRelease = versionParts[1] || 'pl';
let versionFull = versionNumber + '-' + versionRelease;

// Helper: Replace string/regex in a file
async function replaceInFile(filePath, replacements, message = 'file') {
    try {
        let content = await fs.readFile(filePath, 'utf8');
        replacements.forEach(({ search, replace }) => {
            content = content.replace(search, replace);
        });
        await fs.writeFile(filePath, content, 'utf8');
        console.log(`✓ Updated ${message}: ${filePath}`);
    } catch (e) {
        console.warn(`⚠ Error updating ${filePath}`);
    }
}

// Helper: Ensure directory structure exists
async function ensureDirExists(dirPath) {
    await fs.mkdir(path.dirname(dirPath), { recursive: true });
}

// Helper: Copy a file
async function copyFile(src, dest) {
    try {
        const fullSource = path.resolve(__dirname, src);
        const fullTarget = path.resolve(__dirname, dest);
        const targetDir = path.dirname(fullTarget);
        await ensureDirExists(targetDir);
        await fs.copyFile(fullSource, fullTarget);
        console.log(`✓ Copied: ${src} -> ${dest}`);
    } catch (e) {
        console.warn(`⚠ Error copying ${src} -> ${dest}`);
    }
}

// Helper: Copy folders recursively with an optional filter function
async function copyFolderRecursive(src, dest, filterFn = (fileName) => true) {
    await ensureDirExists(dest);
    const entries = await fs.readdir(src, { withFileTypes: true });
    for (let entry of entries) {
        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);

        if (entry.isDirectory()) {
            await copyFolderRecursive(srcPath, destPath, filterFn);
        } else if (filterFn(entry.name)) {
            try {
                await fs.copyFile(srcPath, destPath);
                console.log(`✓ Copied: ${srcPath} -> ${destPath}`);
            } catch (e) {
                console.warn(`⚠ Error copying ${srcPath} -> ${destPath}`);
            }
        }
    }
}

// Helper function to compile scripts
async function compileScripts(files, dest, filename) {
    console.log('Compiling scripts...');
    const fileContents = await Promise.all(
        files.map(f => fs.readFile(f, 'utf8'))
    );
    const minified = await minify(fileContents.join('\n'), {
        mangle: true,
        compress: true,
        format: {
            comments: false,
        },
    });
    const finalCode = banner + minified.code;
    await ensureDirExists(dest);
    filename = filename.replace(/(\.\w+)$/i, '.min$1');
    await fs.writeFile(path.join(dest, filename), finalCode, 'utf8');
}

// Helper function to compile, autoprefix and minify Sass
async function compileSass(files, intermediate, dest, filename) {
    console.log('Compiling Sass & processing CSS...');
    let concatenatedCss = '';
    for (const file of files) {
        const sassResult = await sass.compile(file, { style: 'expanded' });
        const postcssResult = await postcss([
            autoprefixer()
        ])
            .process(sassResult.css, { from: undefined });
        concatenatedCss += postcssResult.css + '\n';
    }
    concatenatedCss = concatenatedCss.replace(/\/\*[\s\(\S\)]*?\*\/\n*/g, '');
    await ensureDirExists(intermediate);
    await fs.writeFile(path.join(intermediate, filename), concatenatedCss, 'utf8');
    const postcssResult = await postcss([
        autoprefixer(),
        cssnano({ preset: ['default', { discardComments: { removeAll: true } }] })
    ])
        .process(concatenatedCss, { from: undefined });
    const finalCss = postcssResult.css + '\n' + banner;
    await ensureDirExists(dest);
    filename = filename.replace(/(\.\w+)$/i, '.min$1');
    await fs.writeFile(path.join(dest, filename), finalCss, 'utf8');
}

async function taskBump() {
    console.log(`Bump (with version ${versionFull} and daterange: ${yearRange})...`);
    const copyrightFiles = [
        'core/components/twofactorx/model/twofactorx/twofactorx.class.php',
        'core/components/twofactorx/src/TwoFactorX.php',
    ];
    for (const file of copyrightFiles) {
        await replaceInFile(file, [{
            search: copyrightRegex,
            replace: copyrightReplace
        }], 'copyright in');
    }
    await replaceInFile('core/components/twofactorx/src/TwoFactorX.php', [{
        search: /version = '\d+\.\d+\.\d+-?[0-9a-z]*'/ig,
        replace: `version = '${version}'`
    }], 'version in');
    const docFiles = [
        'zensical.toml',
    ];
    for (const file of docFiles) {
        await replaceInFile(file, [{
            search: apiRegex,
            replace: apiReplace
        }], 'daterange in');
    }
    await replaceInFile('core/components/twofactorx/composer.json', [{
        search: /"php": "\d.\d.*?"/g,
        replace: '"php": "' + phpversion + '"'
    }, {
        search: /"php": ">=\d.\d.*?"/g,
        replace: '"php": ">=' + phpversion + '"'
    }], 'requirements in');
    const buildFiles = [
        '_build/config.json',
        'core/components/twofactorx/composer.json',
    ];
    for (const file of buildFiles) {
        await replaceInFile(file, [{
            search: /"version": "\d+\.\d+\.\d+-?[0-9a-z]*"/ig,
            replace: `"version": "${version}"`
        }], 'version in');
    }
}

async function taskMgrScripts() {
    await compileScripts([
        'source/js/mgr/twofactorx.js',
    ], 'assets/components/twofactorx/js/mgr/', 'twofactorx.js');
}

async function taskUserTabScripts() {
    await compileScripts([
        'source/js/mgr/usertab.js',
    ], 'assets/components/twofactorx/js/mgr/', 'usertab.js');
}

async function taskUserProfileScripts() {
    await compileScripts([
        'source/js/mgr/userprofile.js',
    ], 'assets/components/twofactorx/js/mgr/', 'userprofile.js');
}

async function taskUserVerifyTotpScripts() {
    await compileScripts([
        'source/js/mgr/userverifytotp.js',
    ], 'assets/components/twofactorx/js/mgr/', 'userverifytotp.js');
}

async function taskSass() {
    await compileSass([
        'source/sass/mgr/twofactorx.scss'
    ], 'source/css/mgr/', 'assets/components/twofactorx/css/mgr/', 'twofactorx.css');
}

const action = process.argv[2];
if (action === 'bump') {
    taskBump();
} else if (action === 'scripts') {
    taskMgrScripts();
    taskUserTabScripts();
    taskUserProfileScripts();
    taskUserVerifyTotpScripts();
} else if (action === 'sass') {
    taskSass();
} else {
    // Default: Beides ausführen
    taskBump()
        .then(() => taskMgrScripts())
        .then(() => taskUserTabScripts())
        .then(() => taskUserProfileScripts())
        .then(() => taskUserVerifyTotpScripts())
        .then(() => taskSass());
}

console.log('Done!');
